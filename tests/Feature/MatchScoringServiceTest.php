<?php

use App\Models\InvestorProfile;
use App\Models\Project;
use App\Models\Sector;
use App\Models\Stage;
use App\Services\Matching\AiSemanticAnalyzer;
use App\Services\Matching\MatchAiResult;
use App\Services\Matching\MatchScoringService;
use Database\Seeders\MatchingWeightsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SectorsSeeder;
use Database\Seeders\StagesSeeder;
use Database\Seeders\SubscriptionPlansSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(SubscriptionPlansSeeder::class);
    $this->seed(SectorsSeeder::class);
    $this->seed(StagesSeeder::class);
    $this->seed(MatchingWeightsSeeder::class);
});

function makeMatchedPair(): array
{
    $sector = Sector::first();
    $stage = Stage::first();

    $project = Project::factory()->create(['business_model' => 'We sell things.']);
    $project->sectors()->sync([$sector->id]);
    $project->stages()->sync([$stage->id]);

    $investor = InvestorProfile::factory()->create();
    $investor->sectors()->sync([$sector->id]);
    $investor->stages()->sync([$stage->id]);

    return [$project->fresh(), $investor->fresh()];
}

it('produces a breakdown covering all 8 criteria whose weights sum to 1.0', function () {
    [$project, $investor] = makeMatchedPair();

    $service = app(MatchScoringService::class); // NullSemanticAnalyzer is the default binding
    $result = $service->score($project, $investor);

    expect($result['breakdown'])->toHaveCount(8);
    expect($result['score'])->toBeGreaterThanOrEqual(0)->toBeLessThanOrEqual(100);

    $totalWeight = array_sum(array_column($result['breakdown'], 'weight'));
    expect(round($totalWeight, 3))->toBe(1.0);
});

it('never lets an AI failure break the score - falls back to the completeness heuristic', function () {
    [$project, $investor] = makeMatchedPair();

    // Bind a fake analyzer that simulates the documented failure
    // contract: it returns null rather than throwing.
    app()->bind(AiSemanticAnalyzer::class, fn () => new class implements AiSemanticAnalyzer
    {
        public function analyze(Project $project, InvestorProfile $investor): ?MatchAiResult
        {
            return null;
        }
    });

    $service = app(MatchScoringService::class);
    $result = $service->score($project, $investor);

    $aiCriteria = collect($result['breakdown'])->whereIn('key', ['business', 'traction', 'impact']);
    expect($aiCriteria->pluck('source')->unique()->all())->toBe(['fallback']);
    expect($result['score'])->toBeGreaterThanOrEqual(0); // still a valid score, not a crash
});

it('uses the AI result when the analyzer succeeds', function () {
    [$project, $investor] = makeMatchedPair();

    app()->bind(AiSemanticAnalyzer::class, fn () => new class implements AiSemanticAnalyzer
    {
        public function analyze(Project $project, InvestorProfile $investor): ?MatchAiResult
        {
            return new MatchAiResult(90, 85, 80, 'Strong alignment.');
        }
    });

    $service = app(MatchScoringService::class);
    $result = $service->score($project, $investor);

    $business = collect($result['breakdown'])->firstWhere('key', 'business');
    expect($business['score'])->toBe(90);
    expect($business['source'])->toBe('ai');
});
