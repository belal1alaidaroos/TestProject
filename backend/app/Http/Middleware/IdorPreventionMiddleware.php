<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class IdorPreventionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        // Get the resource ID from the route parameters
        $resourceId = $request->route('id') ?? $request->route('worker') ?? $request->route('contract') ?? $request->route('proposal');

        if ($resourceId) {
            // Check ownership based on user type and resource
            if (!$this->checkOwnership($user, $resourceId, $request->route()->getName())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
            }
        }

        return $next($request);
    }

    /**
     * Check if the user owns the resource or has appropriate access
     */
    private function checkOwnership($user, $resourceId, $routeName): bool
    {
        // Internal users have access to all resources
        if ($user->user_type === 'Internal') {
            return true;
        }

        // Check ownership based on route name
        switch ($routeName) {
            case 'contracts.show':
            case 'contracts.update':
            case 'contracts.cancel':
            case 'contracts.prepare-payment':
            case 'contracts.confirm-payment':
                return $this->checkContractOwnership($user, $resourceId);

            case 'proposals.show':
            case 'proposals.update':
            case 'proposals.delete':
            case 'proposals.withdraw':
                return $this->checkProposalOwnership($user, $resourceId);

            case 'reservations.show':
            case 'reservations.cancel':
                return $this->checkReservationOwnership($user, $resourceId);

            default:
                return true; // Allow access for other routes
        }
    }

    /**
     * Check if user owns the contract
     */
    private function checkContractOwnership($user, $contractId): bool
    {
        $contract = \App\Models\Contract::find($contractId);
        
        if (!$contract) {
            return false;
        }

        return $user->id === $contract->customer_id;
    }

    /**
     * Check if user owns the proposal
     */
    private function checkProposalOwnership($user, $proposalId): bool
    {
        $proposal = \App\Models\SupplierProposal::find($proposalId);
        
        if (!$proposal) {
            return false;
        }

        return $user->id === $proposal->agency_id;
    }

    /**
     * Check if user owns the reservation
     */
    private function checkReservationOwnership($user, $reservationId): bool
    {
        $reservation = \App\Models\WorkerReservation::find($reservationId);
        
        if (!$reservation) {
            return false;
        }

        return $user->id === $reservation->customer_id;
    }
}