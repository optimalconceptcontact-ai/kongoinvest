<?php

namespace App\Http\Controllers\Entrepreneur;

use App\Enums\SubscriptionFeature;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\Ai\CoachService;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AiCoachController extends Controller
{
    public function ask(Request $request, Project $project, CoachService $service, SubscriptionService $subscriptions): RedirectResponse
    {
        $this->authorize('update', $project); // ownership check — same gate as editing the project

        // Spec §53: gated to the Entrepreneur plan's ai_coach feature —
        // Free entrepreneurs see an upsell in the UI, not this endpoint.
        if (! $subscriptions->hasFeature($request->user(), SubscriptionFeature::AiCoach)) {
            abort(403, "Le coach IA n'est pas disponible sur votre plan actuel.");
        }

        $data = $request->validate(['question' => ['required', 'string', 'max:2000']]);

        try {
            $service->ask($request->user(), $project, $data['question']);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back();
    }
}
