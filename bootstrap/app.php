<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
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
        $exceptions->respond(function (\Symfony\Component\HttpFoundation\Response $response, \Throwable $e, \Illuminate\Http\Request $request) {
            $status = $response->getStatusCode();

            // Inertia::render(...)->toResponse() already handles both the
            // AJAX (X-Inertia header present) and classic full-page-load
            // cases correctly on its own — no need to branch on the
            // header ourselves. Applies to a direct URL visit to a bad
            // route just as much as an in-app navigation.
            if (in_array($status, [401, 403, 404, 419, 429, 500, 503], true) && ! $request->expectsJson()) {
                return \Inertia\Inertia::render('Errors/Error', ['status' => $status])
                    ->toResponse($request)
                    ->setStatusCode($status);
            }

            return $response;
        });
    })->create();
