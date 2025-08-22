<?php

namespace App\Policies;

use App\Models\AppUser;
use App\Models\Worker;
use Illuminate\Auth\Access\HandlesAuthorization;

class WorkerPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any workers.
     */
    public function viewAny(AppUser $user): bool
    {
        return $user->hasPermissionTo('workers.read');
    }

    /**
     * Determine whether the user can view the worker.
     */
    public function view(AppUser $user, Worker $worker): bool
    {
        return $user->hasPermissionTo('workers.read');
    }

    /**
     * Determine whether the user can create workers.
     */
    public function create(AppUser $user): bool
    {
        return $user->hasPermissionTo('workers.create');
    }

    /**
     * Determine whether the user can update the worker.
     */
    public function update(AppUser $user, Worker $worker): bool
    {
        return $user->hasPermissionTo('workers.update');
    }

    /**
     * Determine whether the user can delete the worker.
     */
    public function delete(AppUser $user, Worker $worker): bool
    {
        return $user->hasPermissionTo('workers.delete');
    }

    /**
     * Determine whether the user can reserve the worker.
     */
    public function reserve(AppUser $user, Worker $worker): bool
    {
        // Only customers can reserve workers
        if ($user->user_type !== 'Customer') {
            return false;
        }

        // Worker must be available
        if ($worker->status !== 'Ready') {
            return false;
        }

        return $user->hasPermissionTo('workers.reserve');
    }

    /**
     * Determine whether the user can view worker details.
     */
    public function viewDetails(AppUser $user, Worker $worker): bool
    {
        return $user->hasPermissionTo('workers.read');
    }
}