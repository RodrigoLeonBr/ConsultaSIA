<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Admin access - only admin role
        Gate::define('admin-access', function (User $user) {
            return $user->isAdmin();
        });

        // Operator access - admin or operator roles
        Gate::define('operator-access', function (User $user) {
            return $user->canAccessOperatorFeatures();
        });

        // User management - only admin can manage users
        Gate::define('manage-users', function (User $user) {
            return $user->isAdmin();
        });

        // Activate/deactivate users - only admin
        Gate::define('activate-users', function (User $user) {
            return $user->isAdmin();
        });

        // Change user roles - only admin
        Gate::define('change-roles', function (User $user) {
            return $user->isAdmin();
        });

        // View sensitive data - admin or operator
        Gate::define('view-sensitive-data', function (User $user) {
            return $user->canAccessOperatorFeatures();
        });
    }
}