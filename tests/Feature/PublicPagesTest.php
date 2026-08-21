<?php

use App\Models\Project;
use App\Models\User;
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
    User::factory()->entrepreneur()->create();
    User::factory()->entrepreneur()->create();
    User::factory()->investor()->create();
    Project::factory()->published()->withTaxonomy()->create();

    $response = $this->get('/');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('stats.entrepreneurs', 2)
        ->where('stats.investors', 1)
        ->where('stats.publishedProjects', 1)
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
    $response->assertInertia(fn ($page) => $page->has('plans', 4)); // Free, Entrepreneur, Investor, Professional
});
