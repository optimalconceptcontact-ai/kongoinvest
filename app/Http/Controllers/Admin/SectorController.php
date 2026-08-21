<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin content-management CRUD (Sectors/Stages/Partners/Testimonials/
 * FAQ, spec §131) uses inline $request->validate() rather than dedicated
 * FormRequest classes — a deliberate simplification for this batch of
 * simple, single-model, admin-only forms. Every other domain in this
 * codebase (Projects, Subscriptions, Profiles) uses real FormRequests;
 * this is the one place that doesn't, and it's a scope trade-off, not an
 * oversight — the authorization story (role:admin + permission:
 * admin.dashboard, applied at the route-group level) is unaffected
 * either way.
 */
class SectorController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Sectors/Index', [
            'sectors' => Sector::orderBy('order')->withCount('projects')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:sectors,name'],
            'icon' => ['nullable', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:500'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        Sector::create($data + ['is_active' => true]);

        return back()->with('success', 'Secteur créé.');
    }

    public function update(Request $request, Sector $sector): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:sectors,name,'.$sector->id],
            'icon' => ['nullable', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        $sector->update($data);

        return back()->with('success', 'Secteur mis à jour.');
    }

    public function destroy(Sector $sector): RedirectResponse
    {
        // Soft-guard: don't delete a sector still in use — deactivate
        // instead. Projects/investors referencing it would otherwise
        // silently lose a pivot row.
        if ($sector->projects()->exists() || $sector->investorProfiles()->exists()) {
            return back()->with('error', 'Ce secteur est utilisé par des projets ou investisseurs — désactivez-le plutôt que de le supprimer.');
        }

        $sector->delete();

        return back()->with('success', 'Secteur supprimé.');
    }
}
