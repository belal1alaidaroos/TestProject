<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\AppUser;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Define gates for user types
        Gate::define('is-admin', function (AppUser $user) {
            return $user->user_type === 'Internal';
        });

        Gate::define('is-customer', function (AppUser $user) {
            return $user->user_type === 'Customer';
        });

        Gate::define('is-agency', function (AppUser $user) {
            return $user->user_type === 'Agency';
        });

        // Define resource-based permissions
        Gate::define('manage-workers', function (AppUser $user) {
            return $user->hasPermissionTo('workers.manage');
        });

        Gate::define('manage-contracts', function (AppUser $user) {
            return $user->hasPermissionTo('contracts.manage');
        });

        Gate::define('manage-proposals', function (AppUser $user) {
            return $user->hasPermissionTo('proposals.manage');
        });

        Gate::define('manage-users', function (AppUser $user) {
            return $user->hasPermissionTo('users.manage');
        });

        Gate::define('manage-settings', function (AppUser $user) {
            return $user->hasPermissionTo('settings.manage');
        });
    }
}