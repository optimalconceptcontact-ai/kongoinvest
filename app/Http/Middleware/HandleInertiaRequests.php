<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user?->only(['id', 'name', 'email', 'email_verified_at']),
                // Roles/permissions are shared so React can hide/show UI —
                // this is presentation only. The backend Policies remain
                // the actual authority (spec §100).
                'roles' => $user?->getRoleNames() ?? [],
                'permissions' => $user?->getAllPermissions()->pluck('name') ?? [],
            ],
            // Powers openLogin()/openRegister()/etc. when a protected
            // action redirects an unauthenticated visitor back with an
            // intent to resume (spec §7).
            'authModal' => fn () => $request->session()->get('authModal'),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            // Publishable key — safe to expose client-side by design
            // (Stripe's own naming convention makes this explicit). Never
            // the secret key. Shared globally so any checkout page can
            // mount Stripe Elements without a per-page prop.
            'stripePublishableKey' => config('services.stripe.key'),
        ];
    }
}
