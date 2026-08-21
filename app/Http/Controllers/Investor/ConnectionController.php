<?php

namespace App\Http\Controllers\Investor;

use App\Actions\Connections\CancelConnection;
use App\Actions\Connections\RequestConnection;
use App\Data\ConnectionData;
use App\Http\Controllers\Controller;
use App\Models\Connection;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConnectionController extends Controller
{
    public function index(Request $request): Response
    {
        $investorProfile = $request->user()->investorProfile()->firstOrFail();

        $connections = Connection::where('investor_profile_id', $investorProfile->id)
            ->with(['project', 'investorProfile.user', 'dealRoom'])
            ->latest()
            ->get();

        return Inertia::render('Investor/Connections/Index', [
            'connections' => $connections->map(fn (Connection $c) => ConnectionData::fromModel($c)),
        ]);
    }

    public function store(Request $request, Project $project, RequestConnection $action): RedirectResponse
    {
        $this->authorize('create', Connection::class);

        $data = $request->validate([
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $action->handle($request->user(), $project, $data['message'] ?? null);

        return back()->with('success', 'Demande de connexion envoyée.');
    }

    public function cancel(Request $request, Connection $connection, CancelConnection $action): RedirectResponse
    {
        $this->authorize('cancel', $connection);

        $action->handle($connection);

        return back()->with('success', 'Demande annulée.');
    }
}
