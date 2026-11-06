<?php

use App\Enums\SubscriptionStatus;
use App\Models\Project;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\SubscriptionService;
use Database\Seeders\FaqSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SectorsSeeder;
use Database\Seeders\StagesSeeder;
use Database\Seeders\SubscriptionPlansSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(SubscriptionPlansSeeder::class);
    $this->seed(SectorsSeeder::class);
    $this->seed(StagesSeeder::class);
    $this->seed(FaqSeeder::class);
});

it('shows live counts on the homepage, not hardcoded numbers', function () {
    $owner = User::factory()->entrepreneur()->create();
    User::factory()->entrepreneur()->create();
    User::factory()->investor()->create();
    // The published project must belong to an already-counted
    // entrepreneur — the factory would otherwise create a third one.
    Project::factory()->for($owner)->published()->withTaxonomy()->create();

    $response = $this->get('/');

    $response->assertOk();
    // stats / plans / featuredProjects are Inertia::defer() behind
    // AppServiceProvider's deferred groups (v3 pattern) — the test
    // must resolve them explicitly before asserting.
    $response->assertInertia(fn ($page) => $page
        ->loadDeferredProps(fn ($loaded) => $loaded
            ->where('stats.entrepreneurs', 2)
            ->where('stats.investors', 1)
            ->where('stats.publishedProjects', 1)
        )
    );
});

it('never shows testimonials or partners that were never published', function () {
    // No TestimonialsSeeder/PartnersSeeder exists at all (spec §69) — this
    // asserts the homepage still renders cleanly with genuinely empty
    // arrays, not placeholder content standing in for real ones.
    $response = $this->get('/');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('testimonials', [])
        ->where('partners', [])
    );
});

it('groups FAQ entries by category and supports keyword search', function () {
    $response = $this->get('/faq?q=RDC');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->has('faqs'));
});

it('shows the real subscription plans on the pricing page, not static copy', function () {
    $response = $this->get('/pricing');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->loadDeferredProps(fn ($loaded) => $loaded->has('plans', 4))); // Free, Entrepreneur, Investor, Professional
});

it('passes the plans to the homepage pricing section with nothing marked current for guests', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->loadDeferredProps(fn ($loaded) => $loaded
            ->has('plans', 4)
            ->where('plans.0.key', 'free')->where('plans.0.isCurrent', false)
            ->where('plans.1.key', 'entrepreneur')->where('plans.1.isCurrent', false)
        )
    );
});

it('marks the entrepreneur plan as current in the homepage pricing section', function () {
    $entrepreneur = User::factory()->entrepreneur()->create();
    $service = app(SubscriptionService::class);
    $service->assignFreePlan($entrepreneur);
    $service->switchPlan($entrepreneur, SubscriptionPlan::where('key', 'entrepreneur')->firstOrFail());

    $response = $this->actingAs($entrepreneur)->get('/');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->loadDeferredProps(fn ($loaded) => $loaded
            ->has('plans', 4)
            ->where('plans.0.isCurrent', false)
            ->where('plans.1.key', 'entrepreneur')->where('plans.1.isCurrent', true)
        )
    );
});

it('marks no plan as current on the pricing page for guests', function () {
    $response = $this->get('/pricing');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->loadDeferredProps(fn ($loaded) => $loaded
            ->where('plans.0.key', 'free')->where('plans.0.isCurrent', false)
            ->where('plans.1.key', 'entrepreneur')->where('plans.1.isCurrent', false)
            ->where('plans.2.key', 'investor')->where('plans.2.isCurrent', false)
            ->where('plans.3.key', 'professional')->where('plans.3.isCurrent', false)
        )
    );
});

it('marks the free plan as current for a new entrepreneur', function () {
    $entrepreneur = User::factory()->entrepreneur()->create();
    app(SubscriptionService::class)->assignFreePlan($entrepreneur);

    $response = $this->actingAs($entrepreneur)->get('/pricing');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->loadDeferredProps(fn ($loaded) => $loaded
            ->where('plans.0.key', 'free')->where('plans.0.isCurrent', true)
            ->where('plans.1.key', 'entrepreneur')->where('plans.1.isCurrent', false)
        )
    );
});

it('marks only the investor plan as available to an investor on free', function () {
    $investor = User::factory()->investor()->create();
    app(SubscriptionService::class)->assignFreePlan($investor);

    $response = $this->actingAs($investor)->get('/pricing');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->loadDeferredProps(fn ($loaded) => $loaded
            ->where('plans.0.key', 'free')->where('plans.0.isCurrent', true)->where('plans.0.availableToViewer', true)
            ->where('plans.1.key', 'entrepreneur')->where('plans.1.availableToViewer', false)
            ->where('plans.2.key', 'investor')->where('plans.2.availableToViewer', true)
            ->where('plans.3.key', 'professional')->where('plans.3.availableToViewer', true)
        )
    );
});

it('switching to a paid plan then downgrading to free cancels immediately with no grace period', function () {
    $entrepreneur = User::factory()->entrepreneur()->create();
    $service = app(SubscriptionService::class);
    $service->assignFreePlan($entrepreneur);
    $paid = SubscriptionPlan::where('key', 'entrepreneur')->firstOrFail();
    $free = SubscriptionPlan::where('key', 'free')->firstOrFail();

    $service->switchPlan($entrepreneur, $paid);

    $this->actingAs($entrepreneur)->get('/pricing')->assertOk()
        ->assertInertia(fn ($page) => $page
            ->loadDeferredProps(fn ($loaded) => $loaded
                ->where('plans.1.isCurrent', true)
                ->where('plans.0.isCurrent', false)
            )
        );

    $service->switchPlan($entrepreneur, $free);

    expect($service->currentPlanFor($entrepreneur->fresh())->key)->toBe('free');
    expect($entrepreneur->planSubscriptions()->where('status', SubscriptionStatus::Canceled->value)->count())->toBe(2);

    $this->actingAs($entrepreneur)->get('/pricing')->assertOk()
        ->assertInertia(fn ($page) => $page
            ->loadDeferredProps(fn ($loaded) => $loaded
                ->where('plans.0.isCurrent', true)
                ->where('plans.1.isCurrent', false)
            )
        );
});
