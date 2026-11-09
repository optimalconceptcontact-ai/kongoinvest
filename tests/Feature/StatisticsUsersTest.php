<?php

use App\Enums\KycStatus;
use App\Models\AuditLog;
use App\Models\KycProfile;
use App\Models\User;
use App\Services\Statistics\PeriodResolver;
use App\Services\Statistics\UsersStatisticsService;
use Carbon\CarbonImmutable;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->travelTo(CarbonImmutable::parse('2026-06-15 12:00:00', 'Africa/Kinshasa'));

    $this->admin = User::factory()->create(['email' => 'zz-admin@example.test']);
    $this->admin->assignRole('admin');
    $this->admin->forceFill([
        'created_at' => CarbonImmutable::parse('2025-01-01 00:00:00', 'UTC'),
    ])->saveQuietly();
});

afterEach(function () {
    $this->travelBack();
});

function usersStatisticsReport($response): array
{
    return $response->inertiaProps('report');
}

function usersStatisticsKpis($response): array
{
    return collect(usersStatisticsReport($response)['kpis'])->keyBy('key')->all();
}

function usersStatisticsCreatedAt(User $user, string $at): User
{
    $user->forceFill(['created_at' => CarbonImmutable::parse($at, 'UTC')])->saveQuietly();

    return $user;
}

function usersStatisticsRefreshPermissions(): void
{
    app(PermissionRegistrar::class)->forgetCachedPermissions();
}

it('renders the users statistics page with report, filtered table and options', function () {
    $entrepreneur = User::factory()->entrepreneur()->create();
    $entrepreneur->entrepreneurProfile->forceFill([
        'onboarding_completed' => true,
        'province' => 'kinshasa',
    ])->save();

    $response = $this->actingAs($this->admin)->get('/admin/statistics/users');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Statistics/Users')
        ->where('report.key', 'users')
        ->where('report.period.preset', 'month')
        ->has('report.kpis')
        ->has('report.charts', 5)
        ->has('report.table.columns', 9)
        ->has('table.data')
        ->has('options.filters', 4)
        ->where('options.filters.0.key', 'account_type')
        ->has('options.search_placeholder')
        ->where('options.can_export', true)
    );

    $kpis = usersStatisticsKpis($response);

    expect($kpis['total']['value'])->toBe(2)
        ->and($kpis['entrepreneurs']['value'])->toBe(1)
        ->and($kpis['investors']['value'])->toBe(0)
        ->and($kpis['verified']['value'])->toBe(2)
        ->and($kpis['profiles_complete']['value'])->toBe(1);
});

it('computes verification, kyc and profile completion KPIs from the database', function () {
    $verified = User::factory()->entrepreneur()->create();

    $kyc = KycProfile::factory()->create(['user_id' => $verified->id]);
    $kyc->forceFill(['status' => KycStatus::Approved])->save();

    $incomplete = User::factory()->entrepreneur()->unverified()->create();
    $incomplete->entrepreneurProfile->forceFill(['onboarding_completed' => false])->save();

    $investor = User::factory()->investor()->create();

    $period = app(PeriodResolver::class)->resolve([
        'period' => 'month',
        'month' => 6,
        'year' => 2026,
    ]);

    $kpis = collect(app(UsersStatisticsService::class)->kpis($period, []))->keyBy('key');

    expect($kpis['total']['value'])->toBe(4)
        ->and($kpis['entrepreneurs']['value'])->toBe(2)
        ->and($kpis['investors']['value'])->toBe(1)
        ->and($kpis['verified']['value'])->toBe(3)
        ->and($kpis['unverified']['value'])->toBe(1)
        ->and($kpis['kyc']['value'])->toBe(1)
        ->and($kpis['profiles_complete']['value'])->toBe(2)
        ->and($kpis['profiles_incomplete']['value'])->toBe(1);
});

it('uses inclusive period bounds and compares against the previous period', function () {
    $inJune = usersStatisticsCreatedAt(User::factory()->create(), '2026-06-10 10:00:00');
    $inPreviousMonth = usersStatisticsCreatedAt(User::factory()->create(), '2026-05-10 10:00:00');
    $outside = usersStatisticsCreatedAt(User::factory()->create(), '2026-01-10 10:00:00');

    $period = app(PeriodResolver::class)->resolve([
        'period' => 'month',
        'month' => 6,
        'year' => 2026,
    ]);

    expect($period->start->format('Y-m-d'))->toBe('2026-06-01')
        ->and($period->end->format('Y-m-d'))->toBe('2026-06-30')
        ->and($period->previousStart->format('Y-m-d'))->toBe('2026-05-01')
        ->and($period->previousEnd->format('Y-m-d'))->toBe('2026-05-31');

    $kpis = collect(app(UsersStatisticsService::class)->kpis($period, []))->keyBy('key');

    expect($kpis['new']['value'])->toBe(1)
        ->and($kpis['new']['variation'])->toBe(0.0);

    expect($inJune->fresh())->not->toBeNull()
        ->and($inPreviousMonth->fresh())->not->toBeNull()
        ->and($outside->fresh())->not->toBeNull();
});

it('resolves week, year and custom periods', function () {
    $resolver = app(PeriodResolver::class);

    $week = $resolver->resolve(['period' => 'week']);
    expect($week->start->format('Y-m-d'))->toBe('2026-06-09')
        ->and($week->end->format('Y-m-d'))->toBe('2026-06-15')
        ->and($week->granularity)->toBe('day');

    $year = $resolver->resolve(['period' => 'year', 'year' => 2026]);
    expect($year->start->format('Y-m-d'))->toBe('2026-01-01')
        ->and($year->end->format('Y-m-d'))->toBe('2026-12-31')
        ->and($year->granularity)->toBe('month');

    $custom = $resolver->resolve([
        'period' => 'custom',
        'date_from' => '2026-06-01',
        'date_to' => '2026-06-10',
    ]);
    expect($custom->start->format('Y-m-d'))->toBe('2026-06-01')
        ->and($custom->end->format('Y-m-d'))->toBe('2026-06-10')
        ->and($custom->previousStart->format('Y-m-d'))->toBe('2026-05-22')
        ->and($custom->previousEnd->format('Y-m-d'))->toBe('2026-05-31');
});

it('filters the table by account type, activity, profile, province and search', function () {
    $active = User::factory()->entrepreneur()->create([
        'name' => 'Aline Active',
        'email' => 'aline@example.test',
    ]);
    $active->forceFill(['last_active_at' => now()->subDay()])->saveQuietly();
    $active->entrepreneurProfile->forceFill([
        'onboarding_completed' => true,
        'province' => 'kinshasa',
    ])->save();

    $inactive = User::factory()->investor()->create([
        'name' => 'Bob Inactif',
        'email' => 'bob@example.test',
    ]);
    $inactive->forceFill(['last_active_at' => now()->subDays(90)])->saveQuietly();
    $inactive->investorProfile->forceFill(['onboarding_completed' => false])->save();

    $byType = $this->actingAs($this->admin)
        ->get('/admin/statistics/users?account_type=investor');
    $byType->assertOk();
    expect(usersStatisticsReport($byType)['table']['columns'])->toHaveCount(9)
        ->and(collect($byType->inertiaProps('table')['data'])->pluck('email')->all())
        ->toBe(['bob@example.test']);

    $byActivity = $this->actingAs($this->admin)
        ->get('/admin/statistics/users?account_status=inactive');
    expect(collect($byActivity->inertiaProps('table')['data'])->pluck('email')->all())
        ->toBe(['bob@example.test']);

    $byProvince = $this->actingAs($this->admin)
        ->get('/admin/statistics/users?province=kinshasa');
    expect(collect($byProvince->inertiaProps('table')['data'])->pluck('email')->all())
        ->toBe(['aline@example.test']);

    $byProfile = $this->actingAs($this->admin)
        ->get('/admin/statistics/users?profile_status=incomplete');
    expect(collect($byProfile->inertiaProps('table')['data'])->pluck('email')->all())
        ->toBe(['bob@example.test']);

    $bySearch = $this->actingAs($this->admin)
        ->get('/admin/statistics/users?search=bob');
    expect(collect($bySearch->inertiaProps('table')['data'])->pluck('email')->all())
        ->toBe(['bob@example.test']);
});

it('sorts and paginates the table server-side', function () {
    foreach (range(1, 5) as $index) {
        User::factory()->create(['email' => "user{$index}@example.test"]);
    }

    $response = $this->actingAs($this->admin)
        ->get('/admin/statistics/users?sort=email&direction=asc&per_page=5');

    $response->assertOk();

    $emails = collect($response->inertiaProps('table')['data'])->pluck('email')->all();

    expect($emails)->toBe([
        'user1@example.test',
        'user2@example.test',
        'user3@example.test',
        'user4@example.test',
        'user5@example.test',
    ]);

    $page = $response->inertiaProps('table');
    expect($page['per_page'])->toBe(5)
        ->and($page['total'])->toBe(6)
        ->and($page['last_page'])->toBe(2);
});

it('validates the custom period and the sort whitelist', function () {
    $this->actingAs($this->admin)
        ->get('/admin/statistics/users?period=custom')
        ->assertSessionHasErrors(['date_from', 'date_to']);

    $this->actingAs($this->admin)
        ->get('/admin/statistics/users?sort=password')
        ->assertSessionHasErrors('sort');
});

it('requires statistics.view to read and statistics.export to download', function () {
    $role = Role::findByName('admin');

    $role->revokePermissionTo('statistics.view');
    usersStatisticsRefreshPermissions();

    $this->actingAs($this->admin)
        ->get('/admin/statistics/users')
        ->assertForbidden();

    $role->givePermissionTo('statistics.view');
    $role->revokePermissionTo('statistics.export');
    usersStatisticsRefreshPermissions();

    $this->actingAs($this->admin->fresh())
        ->get('/admin/statistics/users')
        ->assertOk();

    $this->actingAs($this->admin->fresh())
        ->get('/admin/statistics/users/export/csv')
        ->assertForbidden();
});

it('exports the same filtered report as CSV, Excel and PDF and audits each export', function () {
    $investor = User::factory()->investor()->create(['email' => 'investor@example.test']);
    $entrepreneur = User::factory()->entrepreneur()->create(['email' => 'entrepreneur@example.test']);

    $csv = $this->actingAs($this->admin)
        ->get('/admin/statistics/users/export/csv?account_type=investor');

    $csv->assertOk();
    expect($csv->headers->get('content-type'))->toContain('text/csv');

    $csvBody = $csv->streamedContent();
    expect($csvBody)->toContain('Analyse des utilisateurs')
        ->toContain('investor@example.test')
        ->not->toContain('entrepreneur@example.test');

    $xlsx = $this->actingAs($this->admin)
        ->get('/admin/statistics/users/export/xlsx?account_type=investor');

    $xlsx->assertOk();
    expect($xlsx->headers->get('content-type'))->toContain('spreadsheetml')
        ->and(substr($xlsx->streamedContent(), 0, 2))->toBe('PK');

    $pdf = $this->actingAs($this->admin)
        ->get('/admin/statistics/users/export/pdf?account_type=investor');

    $pdf->assertOk();
    expect($pdf->headers->get('content-type'))->toContain('application/pdf')
        ->and(substr($pdf->getContent(), 0, 4))->toBe('%PDF');

    $logs = AuditLog::where('action', 'statistics.export')->get();

    expect($logs)->toHaveCount(3)
        ->and($logs->every(fn (AuditLog $log) => $log->actor_id === $this->admin->id))->toBeTrue()
        ->and($logs->pluck('metadata.format')->all())->toBe(['csv', 'xlsx', 'pdf']);

    expect($investor->fresh())->not->toBeNull()
        ->and($entrepreneur->fresh())->not->toBeNull();
});

it('does not break when the selected filters match no user', function () {
    $response = $this->actingAs($this->admin)
        ->get('/admin/statistics/users?search=does-not-exist');

    $response->assertOk();

    expect($response->inertiaProps('table')['data'])->toBe([])
        ->and($response->inertiaProps('table')['total'])->toBe(0);
});

it('records the last activity of an authenticated user', function () {
    $user = User::factory()->entrepreneur()->create();

    expect($user->fresh()->last_active_at)->toBeNull();

    $this->actingAs($user)->get('/notifications')->assertOk();

    expect($user->fresh()->last_active_at)->not->toBeNull();
});

it('does not rewrite last activity inside the five-minute throttle window', function () {
    $user = User::factory()->entrepreneur()->create();
    $recent = now()->subMinute()->startOfSecond();

    $user->forceFill(['last_active_at' => $recent])->saveQuietly();

    $this->actingAs($user)->get('/notifications')->assertOk();

    expect($user->fresh()->last_active_at->timestamp)->toBe($recent->timestamp);
});
