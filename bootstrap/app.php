<?php

use App\Http\Middleware\EnsureUserHasRole; // ini yang diatur
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function (): void { // juga ini
            // Konteks pelanggan (publik, anonim). Model binding canteen di-wire pada Modul 4.
            Route::middleware('web')
                ->prefix('toko/{store}')
                ->name('customer.')
                ->group(base_path('routes/customer.php'));

            // Konteks operator tenant (internal). scopeBindings tenant->child pada Modul 4.
            Route::middleware(['web', 'auth', 'verified', 'role:tenant'])
                ->prefix('tenant/{tenant}')
                ->name('tenant.')
                ->group(base_path('routes/tenant.php'));

            // Konteks pengelola kantin (internal).
            Route::middleware(['web', 'auth', 'verified', 'role:admin'])
                ->prefix('admin')
                ->name('admin.')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
