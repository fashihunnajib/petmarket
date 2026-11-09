<?php

namespace App\Providers;

use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Scoped: satu instance per request/job; direset di lifecycle berikutnya (tidak bocor).
        $this->app->scoped(
            TenantContext::class,
            fn (): TenantContext => new TenantContext,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Membatasi parameter route agar angka saja (mencegah bentrok 404 pada URL /products/create)
        Route::pattern('product', '[0-9]+');
        Route::pattern('id', '[0-9]+');

        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}