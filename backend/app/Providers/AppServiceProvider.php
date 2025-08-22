<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Gate;
use App\Models\AppUser;
use App\Models\Worker;
use App\Models\Contract;
use App\Models\RecruitmentRequest;
use App\Models\SupplierProposal;
use App\Policies\WorkerPolicy;
use App\Policies\ContractPolicy;
use App\Policies\RecruitmentRequestPolicy;
use App\Policies\SupplierProposalPolicy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Set default string length for SQL Server
        Schema::defaultStringLength(191);

        // Register policies
        Gate::policy(Worker::class, WorkerPolicy::class);
        Gate::policy(Contract::class, ContractPolicy::class);
        Gate::policy(RecruitmentRequest::class, RecruitmentRequestPolicy::class);
        Gate::policy(SupplierProposal::class, SupplierProposalPolicy::class);

        // Define custom gates
        Gate::define('access-admin', function (AppUser $user) {
            return $user->user_type === 'Internal' && $user->hasPermissionTo('admin.access');
        });

        Gate::define('access-customer-portal', function (AppUser $user) {
            return $user->user_type === 'Customer' && $user->hasPermissionTo('customer.access');
        });

        Gate::define('access-agency-portal', function (AppUser $user) {
            return $user->user_type === 'Agency' && $user->hasPermissionTo('agency.access');
        });
    }
}