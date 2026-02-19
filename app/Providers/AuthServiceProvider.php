<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        User::class => UserPolicy::class,
    ];

    public function boot(): void
    {
        // ✅ Superadmin bypass: se l'utente ha uno di questi ruoli, può fare TUTTO
        Gate::before(function ($user, $ability) {
            if ($user?->hasAnyRole(['superadmin', 'super_admin'])) {
                return true;
            }

            return null; // continua con policy/permessi normali
        });
    }
}
