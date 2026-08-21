<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\LogoutResponse;
use Laravel\Fortify\Contracts\RegisterResponse;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    /**
     * Auth is presented as React modals over the current page (spec §6),
     * never as dedicated Fortify Blade views. So instead of registering
     * loginView()/registerView()/etc., we bind Fortify's *response*
     * contracts to redirect back to wherever the user was, with the
     * originally-intended action preserved in the session (spec §7 —
     * "Protected Action Auth Flow"). Inertia turns session `errors` into
     * page props automatically, so a failed login/register just
     * re-renders the current page with the modal open and errors shown.
     */
    public function boot(): void
    {
        $this->bindResponse(LoginResponse::class, fn () => redirect()->intended(route('dashboard', absolute: false)));
        $this->bindResponse(RegisterResponse::class, fn () => redirect()->intended(route('dashboard', absolute: false)));
        $this->bindResponse(TwoFactorLoginResponse::class, fn () => redirect()->intended(route('dashboard', absolute: false)));
        $this->bindResponse(LogoutResponse::class, fn () => redirect('/'));

        Fortify::createUsersUsing(\App\Actions\Fortify\CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(\App\Actions\Fortify\UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(\App\Actions\Fortify\UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(\App\Actions\Fortify\ResetUserPassword::class);

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = strtolower($request->input(Fortify::username())).'|'.$request->ip();

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }

    /**
     * Bind a Fortify response contract to a plain closure-based response.
     */
    protected function bindResponse(string $contract, \Closure $handler): void
    {
        $this->app->singleton($contract, fn () => new class($handler) implements
            LoginResponse,
            LogoutResponse,
            RegisterResponse,
            TwoFactorLoginResponse {
            public function __construct(private \Closure $handler) {}

            public function toResponse($request)
            {
                return call_user_func($this->handler);
            }
        });
    }
}
