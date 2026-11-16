<?php

namespace App\Providers;

use App\Modules\Ordering\Services\ResolveCustomerSession;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(
            TenantContext::class,
            fn (): TenantContext => new TenantContext,
        );
    }

    public function boot(): void
    {
        Route::pattern('product', '[0-9]+');
        Route::pattern('id', '[0-9]+');

        $this->configureDefaults();

        Event::listen(Login::class, function (Login $event): void {
            $user = $event->user;

            // Hanya akun customer yang aktif.
            if ($user->role !== null || ! $user->isActive()) {
                return;
            }

            $request = request();

            // Cari sesi guest berdasarkan token yang valid.
            $session = app(ResolveCustomerSession::class)->current($request);

            if (! $session || $session->user_id !== null) {
                return;
            }

            // Hubungkan sesi guest ke akun customer.
            $session->user_id = $user->id;
            $session->save();
        });
    }

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
