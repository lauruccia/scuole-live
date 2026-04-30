<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        App\Providers\Filament\AdminPanelProvider::class,
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // ✅ Alias middleware Spatie (serve per "role:..." in web.php)
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);

        // ✅ Escludi i webhook Stripe/PayPal dalla verifica CSRF
        $middleware->validateCsrfTokens(except: [
            '/webhook/stripe',
            '/webhook/paypal',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
