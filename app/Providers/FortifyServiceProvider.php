<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            LoginResponseContract::class,
            fn () => new class implements LoginResponseContract
            {
                public function toResponse($request)
                {
                    $user = auth()->user();

                    // Tenant tetap menuju dashboard tenant.
                    if ($user?->hasRole('tenant')) {
                        $tenantId = \DB::table('user_tenant_roles')
                            ->where('user_id', $user->id)
                            ->value('tenant_id');

                        /** @var Tenant|null $tenant */
                        $tenant = $tenantId
                            ? Tenant::find($tenantId)
                            : Tenant::first();

                        if ($tenant) {
                            return redirect()->route('tenant.dashboard', [
                                'tenant' => $tenant->slug ?? $tenant->id,
                            ]);
                        }

                        return redirect('/dashboard');
                    }

                    // Admin tetap menuju dashboard admin.
                    if ($user?->hasRole('admin')) {
                        return redirect()->route('admin.dashboard');
                    }

                    // Customer kembali ke halaman asal setelah login.
                    $redirect = $request->input('redirect');

                    // Login berasal dari halaman utama.
                    if ($redirect === '/') {
                        return redirect()->route('home');
                    }

                    // Login berasal dari katalog toko.
                    if (
                        is_string($redirect) &&
                        preg_match('#\A/toko/([a-zA-Z0-9-]+)\z#', $redirect, $matches)
                    ) {
                        return redirect()->route('customer.home', [
                            'shop' => $matches[1],
                        ]);
                    }

                    return redirect('/dashboard');
                }
            }
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);

        Fortify::authenticateUsing(function (Request $request) {
            $usernameField = Fortify::username();

            $user = User::where(
                $usernameField,
                $request->input($usernameField)
            )->first();

            if (
                $user &&
                Hash::check($request->input('password'), $user->password) &&
                $user->isActive()
            ) {
                return $user;
            }

            return null;
        });
    }

    private function configureViews(): void
    {
        Fortify::loginView(fn () => view('pages::auth.login'));
        Fortify::verifyEmailView(fn () => view('pages::auth.verify-email'));
        Fortify::twoFactorChallengeView(fn () => view('pages::auth.two-factor-challenge'));
        Fortify::confirmPasswordView(fn () => view('pages::auth.confirm-password'));
        Fortify::registerView(fn () => view('pages::auth.register'));
        Fortify::resetPasswordView(fn () => view('pages::auth.reset-password'));
        Fortify::requestPasswordResetLinkView(fn () => view('pages::auth.forgot-password'));
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by(
                $request->session()->get('login.id')
            );
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(
                Str::lower($request->input(Fortify::username()))
                .'|'.$request->ip()
            );

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('passkeys', function (Request $request) {
            $credentialId = $request->input('credential.id');

            return Limit::perMinute(10)->by(
                ($credentialId ?: $request->session()->getId())
                .'|'.$request->ip()
            );
        });
    }
}
