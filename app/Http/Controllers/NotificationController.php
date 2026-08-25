<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()->notifications()->latest()->take(20)->get();

        return response()->json([
            'notifications' => $notifications->map(fn ($n) => [
                'id' => $n->id,
                'type' => $n->data['type'] ?? null,
                'data' => $n->data,
                'readAt' => $n->read_at?->toIso8601String(),
                'createdAt' => $n->created_at->toIso8601String(),
            ]),
            'unreadCount' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    /**
     * Must answer 204, never a redirect: these endpoints are called via
     * router.post() right before router.visit() navigates elsewhere — if
     * Inertia followed a redirect (back()), the user would be bounced off
     * the notification's target page back to the previous one.
     */
    public function markRead(Request $request, string $notification): Response
    {
        // Scoped through the relationship (spec §12's principle applied
        // here too) — a user can only ever mark their own notifications,
        // there's no notification-ID lookup that could reach someone
        // else's.
        $request->user()->notifications()->where('id', $notification)->first()?->markAsRead();

        return response()->noContent();
    }

    public function markAllRead(Request $request): Response
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->noContent();
    }
}
