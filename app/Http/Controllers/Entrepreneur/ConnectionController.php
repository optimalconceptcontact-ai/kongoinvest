<?php

namespace App\Http\Controllers\Entrepreneur;

use App\Actions\Connections\RespondToConnection;
use App\Data\ConnectionData;
use App\Http\Controllers\Controller;
use App\Models\Connection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConnectionController extends Controller
{
    public function index(Request $request): Response
    {
        // Scoped through the user's own projects — an entrepreneur can
        // never see a connection request for someone else's project
        // (spec §12's principle, applied here too).
        $connections = Connection::whereIn('project_id', $request->user()->projects()->pluck('id'))
            ->with(['project', 'investorProfile.user', 'dealRoom'])
            ->latest()
            ->get();

        return Inertia::render('Entrepreneur/Connections/Index', [
            'connections' => $connections->map(fn (Connection $c) => ConnectionData::fromModel($c)),
        ]);
    }

    public function accept(Request $request, Connection $connection, RespondToConnection $action): RedirectResponse
    {
        $this->authorize('respond', $connection);

        $dealRoom = $action->accept($connection);

        return redirect("/deal-rooms/{$dealRoom->id}")->with('success', 'Connexion acceptée — la Deal Room est ouverte.');
    }

    public function reject(Request $request, Connection $connection, RespondToConnection $action): RedirectResponse
    {
        $this->authorize('respond', $connection);

        $action->reject($connection);

        return back()->with('success', 'Demande refusée.');
    }
}
