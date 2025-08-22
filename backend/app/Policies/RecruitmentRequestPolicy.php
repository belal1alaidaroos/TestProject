<?php

namespace App\Policies;

use App\Models\AppUser;
use App\Models\RecruitmentRequest;
use Illuminate\Auth\Access\HandlesAuthorization;

class RecruitmentRequestPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any recruitment requests.
     */
    public function viewAny(AppUser $user): bool
    {
        return $user->hasPermissionTo('recruitment_requests.read');
    }

    /**
     * Determine whether the user can view the recruitment request.
     */
    public function view(AppUser $user, RecruitmentRequest $request): bool
    {
        // Internal users can view all requests
        if ($user->user_type === 'Internal') {
            return $user->hasPermissionTo('recruitment_requests.read');
        }

        // Agencies can view requests they're eligible for
        if ($user->user_type === 'Agency') {
            return $user->hasPermissionTo('recruitment_requests.read');
        }

        return false;
    }

    /**
     * Determine whether the user can create recruitment requests.
     */
    public function create(AppUser $user): bool
    {
        // Only internal users can create recruitment requests
        if ($user->user_type !== 'Internal') {
            return false;
        }

        return $user->hasPermissionTo('recruitment_requests.create');
    }

    /**
     * Determine whether the user can update the recruitment request.
     */
    public function update(AppUser $user, RecruitmentRequest $request): bool
    {
        // Only internal users can update recruitment requests
        if ($user->user_type !== 'Internal') {
            return false;
        }

        return $user->hasPermissionTo('recruitment_requests.update');
    }

    /**
     * Determine whether the user can delete the recruitment request.
     */
    public function delete(AppUser $user, RecruitmentRequest $request): bool
    {
        // Only internal users can delete recruitment requests
        if ($user->user_type !== 'Internal') {
            return false;
        }

        return $user->hasPermissionTo('recruitment_requests.delete');
    }

    /**
     * Determine whether the user can submit proposals for the request.
     */
    public function submitProposal(AppUser $user, RecruitmentRequest $request): bool
    {
        // Only agencies can submit proposals
        if ($user->user_type !== 'Agency') {
            return false;
        }

        // Request must be open or partially awarded
        if (!in_array($request->status, ['Open', 'PartiallyAwarded'])) {
            return false;
        }

        // Request must not be expired
        if ($request->valid_until < now()) {
            return false;
        }

        return $user->hasPermissionTo('recruitment_requests.submit_proposal');
    }

    /**
     * Determine whether the user can review proposals for the request.
     */
    public function reviewProposals(AppUser $user, RecruitmentRequest $request): bool
    {
        // Only internal users can review proposals
        if ($user->user_type !== 'Internal') {
            return false;
        }

        return $user->hasPermissionTo('recruitment_requests.review_proposals');
    }
}