<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    /** Read-only — see App\Http\Controllers\Admin\UserController for why (spec §89's sensitive-action reasoning applies the same way here). */
    public function index(Request $request): Response
    {
        $query = Project::query()->with(['sectors', 'user']);

        if ($status = $request->string('status')->trim()->value()) {
            $query->where('status', $status);
        }

        $projects = $query->latest()->paginate(25)->withQueryString();

        return Inertia::render('Admin/Projects/Index', [
            'projects' => $projects->through(fn (Project $p) => [
                'id' => $p->id,
                'slug' => $p->slug,
                'title' => $p->title,
                'entrepreneurName' => $p->user->name,
                'status' => $p->status->value,
                'statusLabel' => $p->status->label(),
                'sectors' => $p->sectors->pluck('name'),
                'fundingTargetMinor' => $p->funding_target_minor,
                'fundingCurrency' => $p->funding_currency,
                'createdAt' => $p->created_at->toIso8601String(),
            ]),
            'filters' => $request->only('status'),
        ]);
    }
}
