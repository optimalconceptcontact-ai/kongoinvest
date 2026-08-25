<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Escrow;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EscrowController extends Controller
{
    /** Read-only aggregate view — each escrow's full history (events, dispute) still lives on its Deal Room (spec §44). */
    public function index(Request $request): Response
    {
        $query = Escrow::query()->with(['dealRoom.project', 'investor', 'entrepreneur']);

        if ($status = $request->string('status')->trim()->value()) {
            $query->where('status', $status);
        }

        $escrows = $query->latest()->paginate(25)->withQueryString();

        return Inertia::render('Admin/Escrow/Index', [
            'escrows' => $escrows->through(fn (Escrow $e) => [
                'id' => $e->id,
                'dealRoomId' => $e->deal_room_id,
                'projectTitle' => $e->dealRoom->project->title,
                'investorName' => $e->investor->name,
                'entrepreneurName' => $e->entrepreneur->name,
                'amountMinor' => $e->amount_minor,
                'currency' => $e->currency,
                'status' => $e->status->value,
                'statusLabel' => $e->status->label(),
                'createdAt' => $e->created_at->toIso8601String(),
            ]),
            'filters' => $request->only('status'),
        ]);
    }
}
