<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Herd (and most real deployments) terminate TLS in a proxy and
        // forward plain HTTP to PHP — without this, $request->isSecure()
        // is always false, so anything that builds a URL from the current
        // request (e.g. Spatie MediaLibrary's thumbnail URLs) generates
        // http:// even though the browser loaded the page over https://,
        // triggering mixed-content warnings/upgrades.
        $middleware->trustProxies(at: '*');

        // There is no /login page — auth is presented as React modals, so
        // guests bounced by the `auth` middleware go back to the home page
        // with a flashed `authModal` payload instead. AuthModalProvider
        // restores the login modal (plus any pending intent) from it on
        // page load, so the redirect opens the login modal (spec §6, §7).
        $middleware->redirectGuestsTo(function (Request $request) {
            $request->session()->flash('authModal', ['modal' => 'login', 'intent' => null]);

            return route('home');
        });

        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Webhook callers (Stripe, PayPal, Mobile Money operators) can't
        // supply a CSRF token — they're not a browser session. Signature
        // verification inside each provider's handleWebhook() is the
        // real protection here (spec §40), not CSRF, which doesn't apply
        // to server-to-server calls in the first place.
        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
            'stripe/*', // Cashier's auto-registered webhook (cashier.webhook) and payment confirmation page (cashier.payment)
        ]);

        // These gate whole route groups (spec §106) — the actual
        // per-resource authorization always still happens in a Policy
        // (spec §8). Never rely on these aliases alone.
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Spec §137: 401/403/404/419/422/429/500 all get the same clean
        // Inertia error page, never a raw Laravel/PHP stack trace — the
        // status code alone decides the copy, rendered client-side by
        // Pages/Errors/Error.tsx. Validation errors (422) are excluded:
        // those already redirect back with field-level `errors`, which
        // is a better UX than a full error page for a form submission.
        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            $status = $response->getStatusCode();

            // Inertia::render(...)->toResponse() already handles both the
            // AJAX (X-Inertia header present) and classic full-page-load
            // cases correctly on its own — no need to branch on the
            // header ourselves. Applies to a direct URL visit to a bad
            // route just as much as an in-app navigation.
            if (in_array($status, [401, 403, 404, 419, 429, 500, 503], true) && ! $request->expectsJson()) {
                return Inertia::render('Errors/Error', ['status' => $status])
                    ->toResponse($request)
                    ->setStatusCode($status);
            }

            return $response;
        });
    })->create();
