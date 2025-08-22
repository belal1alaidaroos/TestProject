<?php

namespace App\Policies;

use App\Models\AppUser;
use App\Models\SupplierProposal;
use Illuminate\Auth\Access\HandlesAuthorization;

class SupplierProposalPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any proposals.
     */
    public function viewAny(AppUser $user): bool
    {
        return $user->hasPermissionTo('proposals.read');
    }

    /**
     * Determine whether the user can view the proposal.
     */
    public function view(AppUser $user, SupplierProposal $proposal): bool
    {
        // Users can view their own proposals
        if ($user->id === $proposal->agency_id) {
            return true;
        }

        // Internal users can view all proposals
        if ($user->user_type === 'Internal') {
            return $user->hasPermissionTo('proposals.read');
        }

        return false;
    }

    /**
     * Determine whether the user can create proposals.
     */
    public function create(AppUser $user): bool
    {
        // Only agencies can create proposals
        if ($user->user_type !== 'Agency') {
            return false;
        }

        return $user->hasPermissionTo('proposals.create');
    }

    /**
     * Determine whether the user can update the proposal.
     */
    public function update(AppUser $user, SupplierProposal $proposal): bool
    {
        // Users can update their own proposals if they're submitted
        if ($user->id === $proposal->agency_id && $proposal->status === 'Submitted') {
            return true;
        }

        // Internal users can update proposals
        if ($user->user_type === 'Internal') {
            return $user->hasPermissionTo('proposals.update');
        }

        return false;
    }

    /**
     * Determine whether the user can delete the proposal.
     */
    public function delete(AppUser $user, SupplierProposal $proposal): bool
    {
        // Users can delete their own proposals if they're submitted
        if ($user->id === $proposal->agency_id && $proposal->status === 'Submitted') {
            return true;
        }

        // Internal users can delete proposals
        if ($user->user_type === 'Internal') {
            return $user->hasPermissionTo('proposals.delete');
        }

        return false;
    }

    /**
     * Determine whether the user can withdraw the proposal.
     */
    public function withdraw(AppUser $user, SupplierProposal $proposal): bool
    {
        // Users can withdraw their own proposals if they're submitted or partially approved
        if ($user->id === $proposal->agency_id && in_array($proposal->status, ['Submitted', 'PartiallyApproved'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can approve the proposal.
     */
    public function approve(AppUser $user, SupplierProposal $proposal): bool
    {
        // Only internal users can approve proposals
        if ($user->user_type !== 'Internal') {
            return false;
        }

        // Proposal must be submitted
        if ($proposal->status !== 'Submitted') {
            return false;
        }

        return $user->hasPermissionTo('proposals.approve');
    }

    /**
     * Determine whether the user can reject the proposal.
     */
    public function reject(AppUser $user, SupplierProposal $proposal): bool
    {
        // Only internal users can reject proposals
        if ($user->user_type !== 'Internal') {
            return false;
        }

        // Proposal must be submitted
        if ($proposal->status !== 'Submitted') {
            return false;
        }

        return $user->hasPermissionTo('proposals.reject');
    }

    /**
     * Determine whether the user can partially approve the proposal.
     */
    public function partiallyApprove(AppUser $user, SupplierProposal $proposal): bool
    {
        // Only internal users can partially approve proposals
        if ($user->user_type !== 'Internal') {
            return false;
        }

        // Proposal must be submitted
        if ($proposal->status !== 'Submitted') {
            return false;
        }

        return $user->hasPermissionTo('proposals.partially_approve');
    }
}