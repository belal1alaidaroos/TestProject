<?php

namespace App\Policies;

use App\Models\AppUser;
use App\Models\Contract;
use Illuminate\Auth\Access\HandlesAuthorization;

class ContractPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any contracts.
     */
    public function viewAny(AppUser $user): bool
    {
        return $user->hasPermissionTo('contracts.read');
    }

    /**
     * Determine whether the user can view the contract.
     */
    public function view(AppUser $user, Contract $contract): bool
    {
        // Users can view their own contracts
        if ($user->id === $contract->customer_id) {
            return true;
        }

        // Internal users can view all contracts
        if ($user->user_type === 'Internal') {
            return $user->hasPermissionTo('contracts.read');
        }

        return false;
    }

    /**
     * Determine whether the user can create contracts.
     */
    public function create(AppUser $user): bool
    {
        return $user->hasPermissionTo('contracts.create');
    }

    /**
     * Determine whether the user can update the contract.
     */
    public function update(AppUser $user, Contract $contract): bool
    {
        // Users can update their own contracts if they're in draft status
        if ($user->id === $contract->customer_id && $contract->status === 'Draft') {
            return true;
        }

        // Internal users can update contracts
        if ($user->user_type === 'Internal') {
            return $user->hasPermissionTo('contracts.update');
        }

        return false;
    }

    /**
     * Determine whether the user can delete the contract.
     */
    public function delete(AppUser $user, Contract $contract): bool
    {
        // Only internal users can delete contracts
        if ($user->user_type !== 'Internal') {
            return false;
        }

        return $user->hasPermissionTo('contracts.delete');
    }

    /**
     * Determine whether the user can cancel the contract.
     */
    public function cancel(AppUser $user, Contract $contract): bool
    {
        // Users can cancel their own contracts if they're not active
        if ($user->id === $contract->customer_id && !in_array($contract->status, ['Active', 'Completed'])) {
            return true;
        }

        // Internal users can cancel any contract
        if ($user->user_type === 'Internal') {
            return $user->hasPermissionTo('contracts.cancel');
        }

        return false;
    }

    /**
     * Determine whether the user can prepare payment for the contract.
     */
    public function preparePayment(AppUser $user, Contract $contract): bool
    {
        // Only the contract owner can prepare payment
        if ($user->id !== $contract->customer_id) {
            return false;
        }

        // Contract must be awaiting payment
        if ($contract->status !== 'AwaitingPayment') {
            return false;
        }

        return $user->hasPermissionTo('contracts.prepare_payment');
    }

    /**
     * Determine whether the user can confirm payment for the contract.
     */
    public function confirmPayment(AppUser $user, Contract $contract): bool
    {
        // Only the contract owner can confirm payment
        if ($user->id !== $contract->customer_id) {
            return false;
        }

        // Contract must be awaiting payment
        if ($contract->status !== 'AwaitingPayment') {
            return false;
        }

        return $user->hasPermissionTo('contracts.confirm_payment');
    }
}