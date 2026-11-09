<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records the last time an authenticated user touched the application.
 *
 * `users.last_active_at` existed but was never written before statistics
 * needed a real "active" definition. Writes are throttled to once every
 * five minutes per user so a page with many Inertia visits does not turn
 * into a write on every request.
 */
class UpdateLastActiveAt
{
    private const THROTTLE_MINUTES = 5;

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null) {
            $lastActiveAt = $user->last_active_at;

            if ($lastActiveAt === null || $lastActiveAt->lessThan(now()->subMinutes(self::THROTTLE_MINUTES))) {
                $timestamp = now();

                // Query builder update bypasses $fillable and, unlike
                // save(), does not touch `updated_at` — activity is not
                // a profile change.
                $user->newQuery()
                    ->whereKey($user->getKey())
                    ->update(['last_active_at' => $timestamp]);

                $user->setAttribute('last_active_at', $timestamp);
            }
        }

        return $next($request);
    }
}
