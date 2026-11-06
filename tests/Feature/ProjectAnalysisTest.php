<?php

use App\Contracts\ProjectAnalysisProviderInterface;
use App\Models\AiUsage;
use App\Models\Project;
use App\Models\User;
use App\Services\Ai\ProjectAnalysisResult;
use App\Services\SubscriptionService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SubscriptionPlansSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(SubscriptionPlansSeeder::class);
});

function fakeAnalysisResult(): ProjectAnalysisResult
{
    return new ProjectAnalysisResult(
        investmentScore: 78,
        subScores: ['market' => 80, 'team' => 70, 'traction' => 60, 'businessModel' => 75, 'scalability' => 65, 'risk' => 70, 'financialPotential' => 72],
        executiveSummary: 'Solid early-stage project.',
        businessModelAssessment: 'Clear revenue model.',
        marketPotential: 'Large addressable market.',
        problemSolutionFit: 'Well matched.',
        competitiveAdvantage: 'Local network effects.',
        teamAssessment: 'Experienced founder.',
        tractionAssessment: 'Early signs of traction.',
        financialPotential: 'Reasonable projections.',
        scalability: 'Can expand regionally.',
        strengths: ['Strong founder-market fit'],
        weaknesses: ['Limited financial history'],
        risks: ['Currency volatility'],
        redFlags: [],
        dueDiligenceQuestions: ['Ask for bank statements'],
        fundingAssessment: 'Ask is reasonable given traction.',
        recommendation: 'Worth further due diligence.',
        tokensUsed: 1500,
    );
}

it('never lets React trigger an AI call directly - it always goes through the controller and stores a real AiAnalysis row', function () {
    $entrepreneur = User::factory()->entrepreneur()->create();
    $project = Project::factory()->for($entrepreneur)->published()->withTaxonomy()->create();

    app()->bind(ProjectAnalysisProviderInterface::class, fn () => new class implements ProjectAnalysisProviderInterface
    {
        public function analyze(Project $project): ?ProjectAnalysisResult
        {
            return fakeAnalysisResult();
        }
    });

    $this->actingAs($entrepreneur)
        ->post("/projects/{$project->id}/ai-analysis")
        ->assertSessionDoesntHaveErrors();

    $analysis = $project->aiAnalyses()->first();
    expect($analysis->status->value)->toBe('completed');
    expect($analysis->investment_score)->toBe(78);
    expect($analysis->content['disclaimer'])->toContain('ne constituent pas un conseil financier');
});

it('never breaks the page when the AI provider is unavailable - stores a Failed row instead', function () {
    $entrepreneur = User::factory()->entrepreneur()->create();
    $project = Project::factory()->for($entrepreneur)->published()->withTaxonomy()->create();

    app()->bind(ProjectAnalysisProviderInterface::class, fn () => new class implements ProjectAnalysisProviderInterface
    {
        public function analyze(Project $project): ?ProjectAnalysisResult
        {
            return null; // simulates unconfigured / failed provider
        }
    });

    $response = $this->actingAs($entrepreneur)->post("/projects/{$project->id}/ai-analysis");

    $response->assertRedirect(); // never a 500

    $analysis = $project->aiAnalyses()->first();
    expect($analysis->status->value)->toBe('failed');
    expect($analysis->investment_score)->toBeNull();
});

it('enforces the plan\'s monthly AI token quota', function () {
    $entrepreneur = User::factory()->entrepreneur()->create();
    app(SubscriptionService::class)->assignFreePlan($entrepreneur); // Free: 20,000 token quota
    $project = Project::factory()->for($entrepreneur)->published()->withTaxonomy()->create();

    AiUsage::create([
        'user_id' => $entrepreneur->id,
        'type' => 'project_analysis',
        'status' => 'completed',
        'tokens_used' => 25000, // already over Free's quota
        'created_at' => now(),
    ]);

    $this->actingAs($entrepreneur)
        ->post("/projects/{$project->id}/ai-analysis")
        ->assertSessionHasErrors('ai');
});
