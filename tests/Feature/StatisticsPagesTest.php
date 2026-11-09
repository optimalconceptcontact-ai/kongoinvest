<?php

use App\Enums\EscrowStatus;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Escrow;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->travelTo(CarbonImmutable::parse('2026-06-15 12:00:00', 'Africa/Kinshasa'));

    $this->admin = User::factory()->create(['email' => 'zz-admin@example.test']);
    $this->admin->assignRole('admin');
});

afterEach(function () {
    $this->travelBack();
});

function statsPagesKpis($response): array
{
    return collect($response->inertiaProps('report')['kpis'])->keyBy('key')->all();
}

it('renders the entrepreneurs statistics page from real profiles and projects', function () {
    $first = User::factory()->entrepreneur()->create([
        'name' => 'Aline Entrepreneure',
        'email' => 'aline@example.test',
    ]);
    $first->entrepreneurProfile->forceFill([
        'onboarding_completed' => true,
        'province' => 'kinshasa',
    ])->save();

    $project = Project::factory()->published()->withTaxonomy()->for($first)->create([
        'title' => 'Ferme solaire de Kinshasa',
        'funding_target_minor' => 250_000,
        'funding_currency' => 'USD',
    ]);

    $second = User::factory()->entrepreneur()->create(['email' => 'bob@example.test']);
    $second->entrepreneurProfile->forceFill([
        'onboarding_completed' => false,
        'province' => 'kongo_central',
    ])->save();

    $response = $this->actingAs($this->admin)->get('/admin/statistics/entrepreneurs');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Statistics/Entrepreneurs')
        ->where('report.key', 'entrepreneurs')
        ->has('report.charts', 5)
        ->has('report.table.columns', 9)
        ->has('options.filters', 5)
        ->where('options.can_export', true)
    );

    $kpis = statsPagesKpis($response);

    expect($kpis['total']['value'])->toBe(2)
        ->and($kpis['onboarding_complete']['value'])->toBe(1)
        ->and($kpis['with_projects']['value'])->toBe(1)
        ->and($kpis['with_published_project']['value'])->toBe(1)
        ->and($kpis['projects']['value'])->toBe(1)
        ->and($kpis['provinces']['value'])->toBe(2);

    $byProvince = $this->actingAs($this->admin)
        ->get('/admin/statistics/entrepreneurs?province=kinshasa');

    expect(collect($byProvince->inertiaProps('table')['data'])->pluck('email')->all())
        ->toBe(['aline@example.test']);

    $bySector = $this->actingAs($this->admin)
        ->get('/admin/statistics/entrepreneurs?sector_id='.$project->sectors->first()->id);

    expect($bySector->inertiaProps('table')['total'])->toBe(1);

    $csv = $this->actingAs($this->admin)
        ->get('/admin/statistics/entrepreneurs/export/csv?province=kinshasa');

    $csv->assertOk();
    $body = $csv->streamedContent();

    expect($body)->toContain('Analyse des entrepreneurs')
        ->toContain('aline@example.test')
        ->not->toContain('bob@example.test');
});

it('renders the investors statistics page with realized capital per currency', function () {
    $investor = User::factory()->investor()->create([
        'name' => 'Aline Investisseuse',
        'email' => 'aline@example.test',
    ]);
    $investor->investorProfile->forceFill([
        'investor_type' => 'individual',
        'risk_appetite' => 'conservative',
        'country_code' => 'CD',
        'onboarding_completed' => true,
    ])->save();

    Escrow::factory()->held()->create([
        'investor_user_id' => $investor->id,
        'amount_minor' => 150_000,
        'currency' => 'USD',
    ]);

    $other = User::factory()->investor()->create(['email' => 'bob@example.test']);
    $other->investorProfile->forceFill([
        'onboarding_completed' => false,
        'investor_type' => 'angel_investor',
        'risk_appetite' => 'aggressive',
        'country_code' => 'FR',
    ])->save();

    $response = $this->actingAs($this->admin)->get('/admin/statistics/investors');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Statistics/Investors')
        ->where('report.key', 'investors')
        ->has('report.charts', 5)
        ->has('report.table.columns', 10)
        ->has('options.filters', 7)
    );

    $kpis = statsPagesKpis($response);

    expect($kpis['total']['value'])->toBe(2)
        ->and($kpis['onboarding_complete']['value'])->toBe(1)
        ->and($kpis['with_investments']['value'])->toBe(1)
        ->and((float) $kpis['invested_usd']['value'])->toBe(1500.0)
        ->and((float) $kpis['invested_cdf']['value'])->toBe(0.0);

    expect(collect($this->actingAs($this->admin)
        ->get('/admin/statistics/investors?country_code=CD')
        ->inertiaProps('table')['data'])->pluck('email')->all())
        ->toBe(['aline@example.test']);

    expect($this->actingAs($this->admin)
        ->get('/admin/statistics/investors?investor_type=individual')
        ->inertiaProps('table')['total'])->toBe(1);

    expect($this->actingAs($this->admin)
        ->get('/admin/statistics/investors?risk_appetite=conservative')
        ->inertiaProps('table')['total'])->toBe(1);
});

it('renders the projects statistics page with JSON province filtering', function () {
    Project::factory()->published()->withTaxonomy()->create([
        'title' => 'Projet Kinshasa',
        'provinces' => ['kinshasa'],
        'funding_target_minor' => 250_000,
        'funding_currency' => 'USD',
    ]);

    Project::factory()->create([
        'title' => 'Projet Kongo Central',
        'provinces' => ['kongo_central'],
        'funding_target_minor' => 0,
        'funding_currency' => 'CDF',
    ]);

    $response = $this->actingAs($this->admin)->get('/admin/statistics/projects');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Statistics/Projects')
        ->where('report.key', 'projects')
        ->has('report.charts', 6)
        ->has('report.table.columns', 10)
        ->has('options.filters', 6)
    );

    $kpis = statsPagesKpis($response);

    expect($kpis['total']['value'])->toBe(2)
        ->and($kpis['published']['value'])->toBe(1)
        ->and($kpis['draft']['value'])->toBe(1)
        ->and((float) $kpis['target_usd']['value'])->toBe(2500.0);

    expect($this->actingAs($this->admin)
        ->get('/admin/statistics/projects?province=kinshasa')
        ->inertiaProps('table')['total'])->toBe(1);

    expect($this->actingAs($this->admin)
        ->get('/admin/statistics/projects?province=kongo_central')
        ->inertiaProps('table')['total'])->toBe(1);

    $csv = $this->actingAs($this->admin)
        ->get('/admin/statistics/projects/export/csv?province=kinshasa');

    $csv->assertOk();
    $body = $csv->streamedContent();

    expect($body)->toContain('Projet Kinshasa')
        ->toContain('2 500,00 USD')
        ->not->toContain('Projet Kongo Central');

    $this->actingAs($this->admin)
        ->get('/admin/statistics/projects?sort=password')
        ->assertSessionHasErrors('sort');
});

it('renders the investments statistics page from escrows only', function () {
    $investor = User::factory()->investor()->create(['name' => 'Investisseur Réalisé']);
    $pendingInvestor = User::factory()->investor()->create(['name' => 'Investisseur Pipeline']);

    Escrow::factory()->held()->create([
        'investor_user_id' => $investor->id,
        'amount_minor' => 100_000,
        'currency' => 'USD',
    ]);

    Escrow::factory()->create([
        'investor_user_id' => $pendingInvestor->id,
        'status' => EscrowStatus::Pending->value,
        'amount_minor' => 50_000,
        'currency' => 'USD',
    ]);

    $response = $this->actingAs($this->admin)->get('/admin/statistics/investments');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Statistics/Investments')
        ->where('report.key', 'investments')
        ->has('report.charts', 4)
        ->has('report.table.columns', 8)
        ->has('options.filters', 2)
    );

    $kpis = statsPagesKpis($response);

    expect($kpis['total']['value'])->toBe(2)
        ->and($kpis['funded']['value'])->toBe(1)
        ->and($kpis['pending']['value'])->toBe(1)
        ->and((float) $kpis['realized_usd']['value'])->toBe(1000.0)
        ->and((float) $kpis['held_usd']['value'])->toBe(1000.0)
        ->and((float) $kpis['release_rate']['value'])->toBe(0.0);

    $pending = $this->actingAs($this->admin)
        ->get('/admin/statistics/investments?status=pending');

    expect($pending->inertiaProps('table')['total'])->toBe(1)
        ->and(collect($pending->inertiaProps('table')['data'])->pluck('investor')->all())
        ->toBe(['Investisseur Pipeline']);

    $csv = $this->actingAs($this->admin)
        ->get('/admin/statistics/investments/export/csv?status=pending');

    $csv->assertOk();
    expect($csv->streamedContent())->toContain('En attente de financement');
});

it('renders the revenue statistics page from the payment ledger only', function () {
    $plan = SubscriptionPlan::factory()->create([
        'name' => 'Entrepreneur',
        'price_minor' => 2_000,
        'annual_price_minor' => 20_000,
        'currency' => 'USD',
    ]);

    $subscriber = User::factory()->investor()->create(['name' => 'Payeur Abonné']);

    Subscription::factory()->create([
        'user_id' => $subscriber->id,
        'subscription_plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active->value,
        'billing_period' => 'monthly',
        'starts_at' => now(),
    ]);

    Payment::factory()->create([
        'payer_id' => $subscriber->id,
        'purpose' => PaymentPurpose::Subscription->value,
        'status' => PaymentStatus::Succeeded->value,
        'amount_minor' => 2_900,
        'currency' => 'USD',
        'payable_type' => SubscriptionPlan::class,
        'payable_id' => $plan->id,
    ]);

    Payment::factory()->create([
        'payer_id' => $subscriber->id,
        'purpose' => PaymentPurpose::Subscription->value,
        'status' => PaymentStatus::Failed->value,
        'amount_minor' => 1_000,
        'currency' => 'USD',
    ]);

    Payment::factory()->create([
        'payer_id' => $subscriber->id,
        'purpose' => PaymentPurpose::Investment->value,
        'status' => PaymentStatus::Succeeded->value,
        'amount_minor' => 50_000,
        'currency' => 'USD',
    ]);

    $response = $this->actingAs($this->admin)->get('/admin/statistics/revenue');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Statistics/Revenue')
        ->where('report.key', 'revenue')
        ->has('report.charts', 4)
        ->has('report.table.columns', 8)
        ->has('options.filters', 4)
    );

    $kpis = statsPagesKpis($response);

    expect($kpis['total']['value'])->toBe(2)
        ->and($kpis['succeeded']['value'])->toBe(1)
        ->and($kpis['failed']['value'])->toBe(1)
        ->and((float) $kpis['revenue_usd']['value'])->toBe(29.0)
        ->and((float) $kpis['mrr_usd']['value'])->toBe(20.0)
        ->and($kpis['active_subscriptions']['value'])->toBe(1)
        ->and($kpis['investment_payments']['value'])->toBe(1);

    $succeeded = $this->actingAs($this->admin)
        ->get('/admin/statistics/revenue?status=succeeded');

    expect($succeeded->inertiaProps('table')['total'])->toBe(1);

    expect($this->actingAs($this->admin)
        ->get('/admin/statistics/revenue?provider=stripe')
        ->inertiaProps('table')['total'])->toBe(2);

    $csv = $this->actingAs($this->admin)
        ->get('/admin/statistics/revenue/export/csv?status=succeeded');

    $csv->assertOk();
    $body = $csv->streamedContent();

    expect($body)->toContain('Encaissements réussis (USD)')
        ->toContain('29,00 USD')
        ->not->toContain('500,00 USD');
});

it('requires statistics.view on every new page', function () {
    $role = Role::findByName('admin');
    $role->revokePermissionTo('statistics.view');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach ([
        'entrepreneurs',
        'investors',
        'projects',
        'investments',
        'revenue',
    ] as $page) {
        $this->actingAs($this->admin->fresh())
            ->get('/admin/statistics/'.$page)
            ->assertForbidden();
    }
});
