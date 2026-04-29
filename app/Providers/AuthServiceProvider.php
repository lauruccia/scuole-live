<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Policy registrate nell'applicazione.
     * Il Gate::before per il superadmin è già in AppServiceProvider — non duplicare qui.
     */
    protected $policies = [
        User::class => UserPolicy::class,
    ];

    public function boot(): void
    {
        // Le policy vengono registrate automaticamente dal framework tramite $policies.
        // Nessun'altra logica necessaria qui.
    }
}
