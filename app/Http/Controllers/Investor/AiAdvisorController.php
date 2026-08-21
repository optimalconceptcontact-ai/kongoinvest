<?php

namespace App\Http\Controllers\Investor;

use App\Enums\SubscriptionFeature;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\Ai\InvestorAdvisorService;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AiAdvisorController extends Controller
{
    public function ask(Request $request, Project $project, InvestorAdvisorService $service, SubscriptionService $subscriptions): RedirectResponse
    {
        $this->authorize('view', $project);

        if (! $subscriptions->hasFeature($request->user(), SubscriptionFeature::AiInvestmentAdvisor)) {
            abort(403, "Le conseiller IA n'est pas disponible sur votre plan actuel.");
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
