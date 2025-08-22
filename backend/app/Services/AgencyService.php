<?php

namespace App\Services;

use App\Models\RecruitmentRequest;
use App\Models\SupplierProposal;
use App\Repositories\RecruitmentRequestRepository;
use App\Repositories\SupplierProposalRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AgencyService
{
    public function __construct(
        private RecruitmentRequestRepository $requestRepository,
        private SupplierProposalRepository $proposalRepository
    ) {}

    public function getEligibleRequests(string $agencyId, array $filters = [], int $perPage = 15)
    {
        return $this->requestRepository->getEligibleForAgency($agencyId, $filters, $perPage);
    }

    public function submitProposal(string $requestId, string $agencyId, array $proposalData): SupplierProposal
    {
        return DB::transaction(function () use ($requestId, $agencyId, $proposalData) {
            $request = $this->requestRepository->findById($requestId);
            
            if (!$request || $request->status !== 'Open') {
                throw new \Exception('Request is not available for proposals');
            }

            // Check if agency already has an active proposal
            $existingProposal = $this->proposalRepository->getActiveByAgency($requestId, $agencyId);
            if ($existingProposal) {
                throw new \Exception('Agency already has an active proposal for this request');
            }

            // Validate offered quantity doesn't exceed remaining
            $remaining = $this->getRemainingQuantity($requestId);
            if ($proposalData['offered_qty'] > $remaining) {
                throw new \Exception("Offered quantity cannot exceed remaining quantity ({$remaining})");
            }

            $proposal = $this->proposalRepository->create(array_merge($proposalData, [
                'request_id' => $requestId,
                'agency_id' => $agencyId,
                'status' => 'Submitted',
            ]));

            Log::info('Proposal submitted', [
                'request_id' => $requestId,
                'agency_id' => $agencyId,
                'proposal_id' => $proposal->id
            ]);

            return $proposal;
        });
    }

    public function updateProposal(string $proposalId, string $agencyId, array $proposalData): SupplierProposal
    {
        return DB::transaction(function () use ($proposalId, $agencyId, $proposalData) {
            $proposal = $this->proposalRepository->findById($proposalId);
            
            if (!$proposal || $proposal->agency_id !== $agencyId) {
                throw new \Exception('Proposal not found or access denied');
            }

            if ($proposal->status !== 'Submitted') {
                throw new \Exception('Proposal cannot be updated in current state');
            }

            // Validate offered quantity doesn't exceed remaining
            $remaining = $this->getRemainingQuantity($proposal->request_id);
            if ($proposalData['offered_qty'] > $remaining) {
                throw new \Exception("Offered quantity cannot exceed remaining quantity ({$remaining})");
            }

            $updatedProposal = $this->proposalRepository->update($proposalId, $proposalData);

            Log::info('Proposal updated', [
                'proposal_id' => $proposalId,
                'agency_id' => $agencyId
            ]);

            return $updatedProposal;
        });
    }

    public function withdrawProposal(string $proposalId, string $agencyId): void
    {
        DB::transaction(function () use ($proposalId, $agencyId) {
            $proposal = $this->proposalRepository->findById($proposalId);
            
            if (!$proposal || $proposal->agency_id !== $agencyId) {
                throw new \Exception('Proposal not found or access denied');
            }

            if ($proposal->status !== 'Submitted') {
                throw new \Exception('Proposal cannot be withdrawn in current state');
            }

            $this->proposalRepository->update($proposalId, [
                'status' => 'Cancelled'
            ]);

            Log::info('Proposal withdrawn', [
                'proposal_id' => $proposalId,
                'agency_id' => $agencyId
            ]);
        });
    }

    public function getAgencyProposals(string $agencyId, array $filters = [], int $perPage = 15)
    {
        return $this->proposalRepository->getByAgency($agencyId, $filters, $perPage);
    }

    private function getRemainingQuantity(string $requestId): int
    {
        $request = $this->requestRepository->findById($requestId);
        $approvedSum = $this->proposalRepository->getApprovedSum($requestId);
        
        return $request->quantity - $approvedSum;
    }
}