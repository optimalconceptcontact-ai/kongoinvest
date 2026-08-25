<?php

namespace App\Http\Controllers\Investor;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContractController extends Controller
{
    public function index(Request $request): Response
    {
        $contracts = Contract::whereHas('dealRoom', fn ($q) => $q->where('investor_user_id', $request->user()->id))
            ->with(['dealRoom.project', 'dealRoom.entrepreneur'])
            ->latest()
            ->get();

        return Inertia::render('Investor/Contracts/Index', [
            'contracts' => $contracts->map(fn (Contract $c) => [
                'id' => $c->id,
                'dealRoomId' => $c->deal_room_id,
                'projectTitle' => $c->dealRoom->project->title,
                'otherPartyName' => $c->dealRoom->entrepreneur->name,
                'status' => $c->status->value,
                'statusLabel' => $c->status->label(),
                'investmentTypeLabel' => $c->investment_type->label(),
                'amountMinor' => $c->amount_minor,
                'currency' => $c->currency,
                'createdAt' => $c->created_at->toIso8601String(),
            ]),
        ]);
    }
}
