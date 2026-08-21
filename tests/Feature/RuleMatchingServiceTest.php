<?php

use App\Models\InvestorProfile;
use App\Models\Project;
use App\Models\Sector;
use App\Services\Matching\RuleMatchingService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SectorsSeeder;
use Database\Seeders\StagesSeeder;
use Database\Seeders\SubscriptionPlansSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(SubscriptionPlansSeeder::class);
    $this->seed(SectorsSeeder::class);
    $this->seed(StagesSeeder::class);
    $this->rules = new RuleMatchingService;
});

it('scores a perfect sector match at 100', function () {
    $sector = Sector::first();
    $project = Project::factory()->create();
    $project->sectors()->sync([$sector->id]);

    $investor = InvestorProfile::factory()->create();
    $investor->sectors()->sync([$sector->id]);

    expect($this->rules->scoreSector($project->fresh(), $investor->fresh()))->toBe(100);
});

it('scores zero when there is no sector overlap at all', function () {
    [$sectorA, $sectorB] = Sector::take(2)->get();
    $project = Project::factory()->create();
    $project->sectors()->sync([$sectorA->id]);

    $investor = InvestorProfile::factory()->create();
    $investor->sectors()->sync([$sectorB->id]);

    expect($this->rules->scoreSector($project->fresh(), $investor->fresh()))->toBe(0);
});

it('scores geography at 100 when the investor has no stated preference', function () {
    $project = Project::factory()->create(); // country_code defaults to CD
    $investor = InvestorProfile::factory()->create(['preferred_geographies' => null]);

    expect($this->rules->scoreGeography($project, $investor))->toBe(100);
});

it('scores geography low when the investor excludes the DRC', function () {
    $project = Project::factory()->create();
    $investor = InvestorProfile::factory()->create(['preferred_geographies' => ['US', 'FR']]);

    expect($this->rules->scoreGeography($project, $investor))->toBe(20);
});

it('scores funding at 0 when the ticket ranges never overlap', function () {
    $project = Project::factory()->create([
        'funding_min_minor' => 10_000_00,
        'funding_max_minor' => 20_000_00,
        'funding_currency' => 'USD',
    ]);
    $investor = InvestorProfile::factory()->create([
        'ticket_min_minor' => 100_000_00,
        'ticket_max_minor' => 200_000_00,
        'ticket_currency' => 'USD',
    ]);

    expect($this->rules->scoreFunding($project, $investor))->toBe(0);
});

it('scores funding neutrally (50) when currencies differ', function () {
    $project = Project::factory()->create(['funding_currency' => 'CDF']);
    $investor = InvestorProfile::factory()->create([
        'ticket_min_minor' => 10_000_00,
        'ticket_max_minor' => 20_000_00,
        'ticket_currency' => 'USD',
    ]);

    expect($this->rules->scoreFunding($project, $investor))->toBe(50);
});
