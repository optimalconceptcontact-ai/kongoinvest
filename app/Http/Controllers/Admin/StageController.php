<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Stage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StageController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Stages/Index', [
            'stages' => Stage::orderBy('order')->withCount('projects')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:60', 'unique:stages,key', 'alpha_dash'],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        Stage::create($data + ['is_active' => true]);

        return back()->with('success', 'Stade créé.');
    }

    public function update(Request $request, Stage $stage): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        $stage->update($data);

        return back()->with('success', 'Stade mis à jour.');
    }

    public function destroy(Stage $stage): RedirectResponse
    {
        if ($stage->projects()->exists() || $stage->investorProfiles()->exists()) {
            return back()->with('error', 'Ce stade est utilisé par des projets ou investisseurs — désactivez-le plutôt que de le supprimer.');
        }

        $stage->delete();

        return back()->with('success', 'Stade supprimé.');
    }
}
