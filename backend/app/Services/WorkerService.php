<?php

namespace App\Services;

use App\Models\Worker;
use App\Models\WorkerReservation;
use App\Models\Contract;
use App\Repositories\WorkerRepository;
use App\Repositories\ContractRepository;
use App\Repositories\WorkerReservationRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WorkerService
{
    public function __construct(
        private WorkerRepository $workerRepository,
        private ContractRepository $contractRepository,
        private WorkerReservationRepository $reservationRepository
    ) {}

    public function getAvailableWorkers(array $filters = [], int $perPage = 15)
    {
        return $this->workerRepository->getAvailableWorkers($filters, $perPage);
    }

    public function reserveWorker(string $workerId, string $customerId): WorkerReservation
    {
        return DB::transaction(function () use ($workerId, $customerId) {
            $worker = $this->workerRepository->findById($workerId);
            
            if (!$worker || !$worker->isReady()) {
                throw new \Exception('Worker is not available for reservation');
            }

            // Check if worker already has active reservation
            if ($worker->activeReservation) {
                throw new \Exception('Worker is already reserved');
            }

            // Create reservation
            $reservation = $this->reservationRepository->create([
                'worker_id' => $workerId,
                'customer_id' => $customerId,
                'state' => 'AwaitingContract',
                'expires_at' => now()->addSeconds(config('system.reservation_timeout_create', 300)),
            ]);

            // Update worker status
            $this->workerRepository->update($workerId, [
                'status' => 'ReservedAwaitingContract'
            ]);

            Log::info('Worker reserved', [
                'worker_id' => $workerId,
                'customer_id' => $customerId,
                'reservation_id' => $reservation->id
            ]);

            return $reservation;
        });
    }

    public function createContractFromReservation(string $reservationId, array $contractData): Contract
    {
        return DB::transaction(function () use ($reservationId, $contractData) {
            $reservation = $this->reservationRepository->findById($reservationId);
            
            if (!$reservation || $reservation->state !== 'AwaitingContract') {
                throw new \Exception('Invalid reservation state');
            }

            if ($reservation->expires_at < now()) {
                throw new \Exception('Reservation has expired');
            }

            // Create contract
            $contract = $this->contractRepository->create(array_merge($contractData, [
                'customer_id' => $reservation->customer_id,
                'worker_id' => $reservation->worker_id,
                'reservation_id' => $reservationId,
                'status' => 'AwaitingPayment',
            ]));

            // Update reservation
            $this->reservationRepository->update($reservationId, [
                'state' => 'AwaitingPayment',
                'contract_id' => $contract->id,
                'expires_at' => now()->addSeconds(config('system.reservation_timeout_pay', 600)),
            ]);

            // Update worker status
            $this->workerRepository->update($reservation->worker_id, [
                'status' => 'ReservedAwaitingPayment',
                'current_contract_id' => $contract->id,
            ]);

            Log::info('Contract created from reservation', [
                'reservation_id' => $reservationId,
                'contract_id' => $contract->id
            ]);

            return $contract;
        });
    }

    public function activateContract(string $contractId): Contract
    {
        return DB::transaction(function () use ($contractId) {
            $contract = $this->contractRepository->findById($contractId);
            
            if (!$contract || !$contract->isAwaitingPayment()) {
                throw new \Exception('Contract is not in awaiting payment state');
            }

            // Validate consistency
            if ($contract->worker->status !== 'ReservedAwaitingPayment' || 
                $contract->worker->current_contract_id !== $contractId) {
                throw new \Exception('Contract and worker states are inconsistent');
            }

            // Update contract
            $this->contractRepository->update($contractId, [
                'status' => 'Active',
                'signed_at' => now(),
            ]);

            // Update worker
            $this->workerRepository->update($contract->worker_id, [
                'status' => 'AssignedToContract',
            ]);

            // Update reservation
            $this->reservationRepository->update($contract->reservation_id, [
                'state' => 'Completed',
            ]);

            Log::info('Contract activated', [
                'contract_id' => $contractId
            ]);

            return $contract->fresh();
        });
    }

    public function cancelReservation(string $reservationId): void
    {
        DB::transaction(function () use ($reservationId) {
            $reservation = $this->reservationRepository->findById($reservationId);
            
            if (!$reservation) {
                throw new \Exception('Reservation not found');
            }

            // Update reservation
            $this->reservationRepository->update($reservationId, [
                'state' => 'Cancelled',
            ]);

            // Update worker status
            $this->workerRepository->update($reservation->worker_id, [
                'status' => 'Ready',
                'current_contract_id' => null,
            ]);

            // Cancel contract if exists
            if ($reservation->contract_id) {
                $this->contractRepository->update($reservation->contract_id, [
                    'status' => 'Cancelled',
                ]);
            }

            Log::info('Reservation cancelled', [
                'reservation_id' => $reservationId
            ]);
        });
    }

    public function releaseExpiredReservations(): int
    {
        $expiredReservations = $this->reservationRepository->getExpiredReservations();
        $count = 0;

        foreach ($expiredReservations as $reservation) {
            try {
                $this->cancelReservation($reservation->id);
                $count++;
            } catch (\Exception $e) {
                Log::error('Failed to release expired reservation', [
                    'reservation_id' => $reservation->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $count;
    }
}