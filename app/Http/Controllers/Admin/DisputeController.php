<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Escrow\ResolveDispute;
use App\Http\Controllers\Controller;
use App\Models\Dispute;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DisputeController extends Controller
{
    public function index(): Response
    {
        $disputes = Dispute::whereIn('status', ['opened', 'under_review'])
            ->with(['escrow.dealRoom.project', 'raisedBy'])
            ->oldest()
            ->get();

        return Inertia::render('Admin/Disputes/Index', [
            'disputes' => $disputes->map(fn (Dispute $d) => [
                'id' => $d->id,
                'reason' => $d->reason,
                'status' => $d->status->value,
                'raisedByName' => $d->raisedBy->name,
                'projectTitle' => $d->escrow->dealRoom->project->title,
                'amountMinor' => $d->escrow->amount_minor,
                'currency' => $d->escrow->currency,
                'createdAt' => $d->created_at->toIso8601String(),
            ]),
        ]);
    }

    public function resolve(Request $request, Dispute $dispute, ResolveDispute $action): RedirectResponse
    {
        $this->authorize('resolve', Dispute::class);

        $data = $request->validate([
            'resolution' => ['required', Rule::in(['release', 'refund', 'reject'])],
            'notes' => ['required', 'string', 'max:2000'],
        ]);

        $action->handle($request->user(), $dispute, $data['resolution'], $data['notes']);

        return back()->with('success', 'Litige résolu.');
    }
}
