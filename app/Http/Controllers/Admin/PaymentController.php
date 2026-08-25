<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Payment::query()->with('payer');

        if ($status = $request->string('status')->trim()->value()) {
            $query->where('status', $status);
        }

        $payments = $query->latest()->paginate(25)->withQueryString();

        return Inertia::render('Admin/Payments/Index', [
            'payments' => $payments->through(fn (Payment $p) => [
                'id' => $p->id,
                'payerName' => $p->payer->name,
                'purposeLabel' => $p->purpose->label(),
                'providerLabel' => $p->provider->label(),
                'amountMinor' => $p->amount_minor,
                'currency' => $p->currency,
                'status' => $p->status->value,
                'statusLabel' => $p->status->label(),
                'failureReason' => $p->failure_reason,
                'createdAt' => $p->created_at->toIso8601String(),
            ]),
            'filters' => $request->only('status'),
        ]);
    }
}
