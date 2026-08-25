<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContractController extends Controller
{
    /** Read-only aggregate view — each contract's versions/negotiation still live on its Deal Room (spec §44). */
    public function index(Request $request): Response
    {
        $query = Contract::query()->with(['dealRoom.project', 'dealRoom.investor', 'dealRoom.entrepreneur']);

        if ($status = $request->string('status')->trim()->value()) {
            $query->where('status', $status);
        }

        $contracts = $query->latest()->paginate(25)->withQueryString();

        return Inertia::render('Admin/Contracts/Index', [
            'contracts' => $contracts->through(fn (Contract $c) => [
                'id' => $c->id,
                'dealRoomId' => $c->deal_room_id,
                'projectTitle' => $c->dealRoom->project->title,
                'investorName' => $c->dealRoom->investor->name,
                'entrepreneurName' => $c->dealRoom->entrepreneur->name,
                'status' => $c->status->value,
                'statusLabel' => $c->status->label(),
                'investmentTypeLabel' => $c->investment_type->label(),
                'amountMinor' => $c->amount_minor,
                'currency' => $c->currency,
                'createdAt' => $c->created_at->toIso8601String(),
            ]),
            'filters' => $request->only('status'),
        ]);
    }
}
