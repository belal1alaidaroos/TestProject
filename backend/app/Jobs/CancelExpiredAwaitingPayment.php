<?php

namespace App\Jobs;

use App\Models\Contract;
use App\Models\Worker;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CancelExpiredAwaitingPayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting CancelExpiredAwaitingPayment job');

        try {
            // Get contracts that are awaiting payment and have expired
            $expiredContracts = Contract::where('status', 'AwaitingPayment')
                ->where('payment_deadline', '<', now())
                ->with(['customer', 'worker'])
                ->get();

            $cancelledCount = 0;

            foreach ($expiredContracts as $contract) {
                try {
                    // Update contract status
                    $contract->update([
                        'status' => 'Cancelled',
                        'modified_by' => 'system',
                        'cancellation_reason' => 'Payment deadline expired',
                    ]);

                    // Release the worker
                    $contract->worker->update([
                        'status' => 'Ready',
                        'modified_by' => 'system',
                    ]);

                    $cancelledCount++;

                    Log::info('Cancelled expired awaiting payment contract', [
                        'contract_id' => $contract->id,
                        'worker_id' => $contract->worker_id,
                        'customer_id' => $contract->customer_id,
                        'total_amount' => $contract->total_amount,
                    ]);

                    // TODO: Send notification to customer about cancelled contract
                    // $this->sendCancelledContractNotification($contract);

                } catch (\Exception $e) {
                    Log::error('Failed to cancel expired contract', [
                        'contract_id' => $contract->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('CancelExpiredAwaitingPayment job completed', [
                'total_expired' => $expiredContracts->count(),
                'cancelled_count' => $cancelledCount,
            ]);

        } catch (\Exception $e) {
            Log::error('CancelExpiredAwaitingPayment job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Send notification to customer about cancelled contract
     */
    private function sendCancelledContractNotification(Contract $contract): void
    {
        // TODO: Implement notification logic
        // This could be SMS, email, or push notification
        // For now, just log the event
        Log::info('Cancelled contract notification should be sent', [
            'contract_id' => $contract->id,
            'customer_phone' => $contract->customer->phone,
            'total_amount' => $contract->total_amount,
        ]);
    }
}