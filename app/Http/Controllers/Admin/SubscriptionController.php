<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Subscription::query()->with(['user', 'plan']);

        if ($status = $request->string('status')->trim()->value()) {
            $query->where('status', $status);
        }

        $subscriptions = $query->latest()->paginate(25)->withQueryString();

        return Inertia::render('Admin/Subscriptions/Index', [
            'subscriptions' => $subscriptions->through(fn (Subscription $s) => [
                'id' => $s->id,
                'userName' => $s->user->name,
                'userEmail' => $s->user->email,
                'planName' => $s->plan->name,
                'priceMinor' => $s->plan->price_minor,
                'currency' => $s->plan->currency,
                'status' => $s->status->value,
                'statusLabel' => $s->status->label(),
                'startsAt' => $s->starts_at->toIso8601String(),
                'endsAt' => $s->ends_at?->toIso8601String(),
            ]),
            'filters' => $request->only('status'),
        ]);
    }
}
