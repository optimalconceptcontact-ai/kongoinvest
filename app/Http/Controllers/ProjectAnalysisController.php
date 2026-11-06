<?php

namespace App\Http\Controllers;

use App\Enums\AiRequestStatus;
use App\Models\AiAnalysis;
use App\Models\Project;
use App\Services\Ai\ProjectAnalysisService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Spec §50: the "Analyze with AI" button on Project Detail. This
 * controller is the ONLY thing that talks to ProjectAnalysisService —
 * React never calls an AI provider directly (spec §56). The result is
 * picked up by Public\ProjectController::show()'s own `aiAnalysis` prop
 * on the next page load, not returned directly from here.
 */
class ProjectAnalysisController extends Controller
{
    public function store(Request $request, Project $project, ProjectAnalysisService $service): RedirectResponse
    {
        $this->authorize('view', $project); // must be able to see the project at all
        $this->authorize('create', AiAnalysis::class);

        try {
            $analysis = $service->analyze($request->user(), $project);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        } catch (\Throwable $e) {
            // Spec §113: an AI failure must never break the page. Log
            // it, tell the user cleanly, let them retry — never a 500.
            Log::error('Project analysis request failed unexpectedly', ['project_id' => $project->id, 'message' => $e->getMessage()]);

            return back()->with('error', "L'analyse n'a pas pu être générée. Réessayez dans quelques instants.");
        }

        // The service persists a Failed row instead of throwing when the
        // provider is unavailable/misconfigured — don't flash success over it.
        if ($analysis->status === AiRequestStatus::Failed) {
            return back()->with('error', $analysis->failure_reason ?? "L'analyse IA n'a pas pu être générée.");
        }

        return back()->with('success', 'Analyse IA générée.');
    }
}
