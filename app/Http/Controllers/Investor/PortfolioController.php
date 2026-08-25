<?php

namespace App\Http\Controllers\Investor;

use App\Enums\EscrowStatus;
use App\Http\Controllers\Controller;
use App\Models\Escrow;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PortfolioController extends Controller
{
    /** Statuses that represent capital currently or previously committed — not a cancelled/pending attempt. */
    private const COMMITTED_STATUSES = [
        EscrowStatus::Funded, EscrowStatus::Held, EscrowStatus::AwaitingRelease,
        EscrowStatus::Released, EscrowStatus::Disputed,
    ];

    public function index(Request $request): Response
    {
        $escrows = Escrow::where('investor_user_id', $request->user()->id)
            ->with(['dealRoom.project', 'dealRoom.entrepreneur', 'contract'])
            ->latest()
            ->get();

        $committed = $escrows->filter(fn (Escrow $e) => in_array($e->status, self::COMMITTED_STATUSES, true));

        $totalsByCurrency = $committed->groupBy('currency')
            ->map(fn ($group) => $group->sum('amount_minor'));

        return Inertia::render('Investor/Portfolio/Index', [
            'summary' => [
                'totalInvestments' => $escrows->count(),
                'activeCount' => $escrows->whereIn('status', [EscrowStatus::Held, EscrowStatus::AwaitingRelease])->count(),
                'releasedCount' => $escrows->where('status', EscrowStatus::Released)->count(),
                'totalsByCurrency' => $totalsByCurrency,
            ],
            'investments' => $escrows->map(fn (Escrow $e) => [
                'id' => $e->id,
                'dealRoomId' => $e->dealRoom->id,
                'projectTitle' => $e->dealRoom->project->title,
                'entrepreneurName' => $e->dealRoom->entrepreneur->name,
                'amountMinor' => $e->amount_minor,
                'currency' => $e->currency,
                'status' => $e->status->value,
                'statusLabel' => $e->status->label(),
                'investmentType' => $e->contract->investment_type->value,
                'investmentTypeLabel' => $e->contract->investment_type->label(),
                'fundedAt' => $e->funded_at?->toIso8601String(),
                'releasedAt' => $e->released_at?->toIso8601String(),
            ])->values(),
        ]);
    }
}
