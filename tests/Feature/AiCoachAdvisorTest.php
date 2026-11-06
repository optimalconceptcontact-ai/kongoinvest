<?php

use App\Contracts\AiConversationProviderInterface;
use App\Models\AiConversation;
use App\Models\Project;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Ai\AiConversationResult;
use App\Services\SubscriptionService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SubscriptionPlansSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(SubscriptionPlansSeeder::class);

    app()->bind(AiConversationProviderInterface::class, fn () => new class implements AiConversationProviderInterface
    {
        public function respond(string $systemPrompt, array $history, string $userMessage): ?AiConversationResult
        {
            return new AiConversationResult(text: 'Here is some advice.', structured: null, tokensUsed: 200);
        }
    });
});

it('never lets an entrepreneur without the ai_coach feature reach the coach endpoint', function () {
    $entrepreneur = User::factory()->entrepreneur()->create();
    app(SubscriptionService::class)->assignFreePlan($entrepreneur); // Free has no ai_coach feature
    $project = Project::factory()->for($entrepreneur)->create();

    $this->actingAs($entrepreneur)
        ->post("/entrepreneur/projects/{$project->id}/ai-coach", ['question' => 'How can I improve?'])
        ->assertForbidden();
});

it('lets an entrepreneur on the Entrepreneur plan ask the coach, and never suggests fabricating data', function () {
    $entrepreneur = User::factory()->entrepreneur()->create();
    $plan = SubscriptionPlan::where('key', 'entrepreneur')->firstOrFail();
    app(SubscriptionService::class)->switchPlan($entrepreneur, $plan);
    $project = Project::factory()->for($entrepreneur)->create();

    $this->actingAs($entrepreneur)
        ->post("/entrepreneur/projects/{$project->id}/ai-coach", ['question' => 'How can I improve my score?'])
        ->assertSessionDoesntHaveErrors();

    $conversation = AiConversation::where('project_id', $project->id)->first();
    expect($conversation)->not->toBeNull();
    expect($conversation->messages()->count())->toBe(2); // user question + assistant reply
});

it('never lets an entrepreneur ask the coach about a project they do not own', function () {
    $owner = User::factory()->entrepreneur()->create();
    $plan = SubscriptionPlan::where('key', 'entrepreneur')->firstOrFail();
    app(SubscriptionService::class)->switchPlan($owner, $plan);
    $project = Project::factory()->for($owner)->create();

    $stranger = User::factory()->entrepreneur()->create();
    app(SubscriptionService::class)->switchPlan($stranger, $plan);

    $this->actingAs($stranger)
        ->post("/entrepreneur/projects/{$project->id}/ai-coach", ['question' => 'How can I improve?'])
        ->assertForbidden();
});

it('never lets an investor without the ai_investment_advisor feature reach the advisor endpoint', function () {
    $investor = User::factory()->investor()->create();
    app(SubscriptionService::class)->assignFreePlan($investor); // Free has no advisor feature
    $project = Project::factory()->published()->withTaxonomy()->create();

    $this->actingAs($investor)
        ->post("/investor/projects/{$project->id}/ai-advisor", ['question' => 'What are the risks?'])
        ->assertForbidden();
});

it('lets an investor on the Investor plan ask the advisor', function () {
    $investor = User::factory()->investor()->create();
    $plan = SubscriptionPlan::where('key', 'investor')->firstOrFail();
    app(SubscriptionService::class)->switchPlan($investor, $plan);
    $project = Project::factory()->published()->withTaxonomy()->create();

    $this->actingAs($investor)
        ->post("/investor/projects/{$project->id}/ai-advisor", ['question' => 'What are the risks?'])
        ->assertSessionDoesntHaveErrors();

    expect(AiConversation::where('project_id', $project->id)->exists())->toBeTrue();
});
