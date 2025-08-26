<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\AppUser;
use App\Models\Worker;
use App\Models\Contract;
use App\Models\RecruitmentRequest;
use App\Models\SupplierProposal;
use App\Models\WorkerReservation;
use App\Policies\WorkerPolicy;
use App\Policies\ContractPolicy;
use App\Policies\RecruitmentRequestPolicy;
use App\Policies\SupplierProposalPolicy;
use App\Policies\WorkerReservationPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Worker::class => WorkerPolicy::class,
        Contract::class => ContractPolicy::class,
        RecruitmentRequest::class => RecruitmentRequestPolicy::class,
        SupplierProposal::class => SupplierProposalPolicy::class,
        WorkerReservation::class => WorkerReservationPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
        
        // Define custom gates
        $this->defineGates();
    }
    
    /**
     * Define custom authorization gates
     */
    private function defineGates(): void
    {
        // Admin access gates
        Gate::define('access-admin', function (AppUser $user) {
            return $user->user_type === 'Internal' && $user->hasPermissionTo('admin.access');
        });
        
        Gate::define('access-customer-portal', function (AppUser $user) {
            return $user->user_type === 'Customer' && $user->hasPermissionTo('customer.access');
        });
        
        Gate::define('access-agency-portal', function (AppUser $user) {
            return $user->user_type === 'Agency' && $user->hasPermissionTo('agency.access');
        });
        
        // Resource-specific gates
        Gate::define('manage-users', function (AppUser $user) {
            return $user->hasPermissionTo('users.manage');
        });
        
        Gate::define('manage-lookups', function (AppUser $user) {
            return $user->hasPermissionTo('lookups.manage');
        });
        
        Gate::define('review-proposals', function (AppUser $user) {
            return $user->hasPermissionTo('proposals.review');
        });
        
        Gate::define('manage-contracts', function (AppUser $user) {
            return $user->hasPermissionTo('contracts.manage');
        });
        
        Gate::define('manage-workers', function (AppUser $user) {
            return $user->hasPermissionTo('workers.manage');
        });
        
        // Agency-specific gates
        Gate::define('submit-proposals', function (AppUser $user) {
            return $user->user_type === 'Agency' && $user->hasPermissionTo('proposals.submit');
        });
        
        Gate::define('manage-own-proposals', function (AppUser $user, SupplierProposal $proposal) {
            return $user->id === $proposal->agency_id;
        });
        
        // Customer-specific gates
        Gate::define('reserve-workers', function (AppUser $user) {
            return $user->user_type === 'Customer' && $user->hasPermissionTo('workers.reserve');
        });
        
        Gate::define('manage-own-contracts', function (AppUser $user, Contract $contract) {
            return $user->id === $contract->customer_id;
        });
        
        Gate::define('manage-own-reservations', function (AppUser $user, WorkerReservation $reservation) {
            return $user->id === $reservation->customer_id;
        });
        
        // Worker-specific gates
        Gate::define('view-worker', function (AppUser $user, Worker $worker) {
            // Customers can view available workers
            if ($user->user_type === 'Customer') {
                return $worker->status === 'Ready';
            }
            
            // Admins and internal users can view all workers
            if (in_array($user->user_type, ['Internal', 'Admin'])) {
                return true;
            }
            
            // Agencies can view workers they recruited
            if ($user->user_type === 'Agency') {
                return $worker->agency_id === $user->id;
            }
            
            return false;
        });
        
        // Request-specific gates
        Gate::define('view-request', function (AppUser $user, RecruitmentRequest $request) {
            // Admins and internal users can view all requests
            if (in_array($user->user_type, ['Internal', 'Admin'])) {
                return true;
            }
            
            // Agencies can view requests they're eligible for
            if ($user->user_type === 'Agency') {
                // Check if agency is eligible based on routing rules
                return $this->isAgencyEligibleForRequest($user, $request);
            }
            
            return false;
        });
    }
    
    /**
     * Check if agency is eligible for a specific request
     */
    private function isAgencyEligibleForRequest(AppUser $agency, RecruitmentRequest $request): bool
    {
        // This is a simplified check - in production you'd have more complex routing rules
        // For now, we'll assume agencies can see all requests
        return true;
    }
}