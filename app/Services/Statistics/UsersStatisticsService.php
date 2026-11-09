<?php

namespace App\Services\Statistics;

use App\Enums\DrcProvince;
use App\Enums\KycStatus;
use App\Models\EntrepreneurProfile;
use App\Models\InvestorProfile;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class UsersStatisticsService extends StatisticsService
{
    public const KEY = 'users';

    /** @var array<int, string> */
    private const ROLE_ORDER = ['entrepreneur', 'investor', 'admin'];

    /** @var array<string, string> */
    private const ROLE_LABELS = [
        'entrepreneur' => 'Entrepreneur',
        'investor' => 'Investisseur',
        'admin' => 'Administrateur',
    ];

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function kpis(StatisticsPeriod $period, array $filters): array
    {
        $base = $this->baseQuery($filters);

        $total = (clone $base)->count();
        $entrepreneurs = (clone $base)->role('entrepreneur')->count();
        $investors = (clone $base)->role('investor')->count();
        $new = $this->applyPeriod(clone $base, 'created_at', $period)->count();
        $previousNew = $this->applyPreviousPeriod(clone $base, 'created_at', $period)->count();
        $active = $this->activeInPeriod(clone $base, $period)->count();
        $previousActive = $this->activeInPeriod(clone $base, $period, previous: true)->count();
        $verified = (clone $base)->whereNotNull('email_verified_at')->count();
        $kycApproved = (clone $base)
            ->whereHas('kycProfile', fn (Builder $query) => $query->where('status', KycStatus::Approved->value))
            ->count();
        $profilesTotal = $this->profilesQuery($base)->count();
        $profilesComplete = $this->completeProfilesQuery($base)->count();

        return [
            $this->kpi('total', 'Utilisateurs', $total, 'integer', hint: 'Comptes non supprimés'),
            $this->kpi('entrepreneurs', 'Entrepreneurs', $entrepreneurs, 'integer'),
            $this->kpi('investors', 'Investisseurs', $investors, 'integer'),
            $this->kpi('new', 'Nouveaux inscrits', $new, 'integer', $this->variation($new, $previousNew), $period->previousLabel),
            $this->kpi('active', 'Actifs', $active, 'integer', $this->variation($active, $previousActive), $period->previousLabel, 'Activité enregistrée sur la période'),
            $this->kpi('inactive', 'Inactifs', max(0, $total - $active), 'integer', hint: 'Aucune activité sur la période'),
            $this->kpi('verified', 'Comptes vérifiés', $verified, 'integer', hint: 'Adresse e-mail confirmée'),
            $this->kpi('unverified', 'Comptes non vérifiés', max(0, $total - $verified), 'integer'),
            $this->kpi('kyc', 'KYC approuvés', $kycApproved, 'integer'),
            $this->kpi('profiles_complete', 'Profils complets', $profilesComplete, 'integer', hint: "Sur {$profilesTotal} profils entrepreneur/investisseur"),
            $this->kpi('profiles_incomplete', 'Profils incomplets', max(0, $profilesTotal - $profilesComplete), 'integer'),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function charts(StatisticsPeriod $period, array $filters): array
    {
        return [
            $this->registrationsChart($period, $filters),
            $this->categoryChart($filters),
            $this->activeChart($period, $filters),
            $this->profileCompletionChart($period, $filters),
            $this->profileDistributionChart($filters),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function tableQuery(array $filters): Builder
    {
        return $this->baseQuery($filters)
            ->with('roles')
            ->with(['entrepreneurProfile', 'investorProfile'])
            ->withCount(['projects', 'investments', 'financedProjects'])
            ->withMax('auditLogs as last_audit_at', 'created_at');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginatedTable(array $filters): LengthAwarePaginator
    {
        return $this->paginate(
            $this->tableQuery($filters),
            $filters,
            ['name', 'email', 'created_at', 'last_active_at'],
            'created_at',
        )->through(fn (User $user) => $this->mapRow($user));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    public function allTableRows(array $filters): Collection
    {
        $limit = (int) config('statistics.export_row_limit', 5000);
        $sortable = ['name', 'email', 'created_at', 'last_active_at'];
        $sort = in_array($filters['sort'] ?? null, $sortable, true) ? $filters['sort'] : 'created_at';
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $this->tableQuery($filters)
            ->orderBy($sort, $direction)
            ->limit($limit)
            ->get()
            ->map(fn (User $user) => $this->mapRow($user));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function tableColumns(): array
    {
        return [
            ['key' => 'name', 'label' => 'Nom', 'sortable' => true, 'type' => 'text'],
            ['key' => 'email', 'label' => 'E-mail', 'sortable' => true, 'type' => 'text'],
            ['key' => 'type', 'label' => 'Type', 'sortable' => false, 'type' => 'text'],
            ['key' => 'created_at', 'label' => 'Inscription', 'sortable' => true, 'type' => 'date'],
            ['key' => 'last_active_at', 'label' => 'Dernière activité', 'sortable' => true, 'type' => 'date'],
            ['key' => 'status', 'label' => 'Statut', 'sortable' => false, 'type' => 'badge'],
            ['key' => 'projects_count', 'label' => 'Projets', 'sortable' => false, 'type' => 'number'],
            ['key' => 'investments_count', 'label' => 'Investissements', 'sortable' => false, 'type' => 'number'],
            ['key' => 'profile_complete', 'label' => 'Profil', 'sortable' => false, 'type' => 'text'],
        ];
    }

    protected function title(): string
    {
        return 'Analyse des utilisateurs';
    }

    protected function description(): string
    {
        return 'Inscriptions, activité, vérification et complétude des profils de la plateforme.';
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function baseQuery(array $filters): Builder
    {
        $query = User::query();

        if (! empty($filters['account_type'])) {
            $query->role((string) $filters['account_type']);
        }

        if (! empty($filters['province'])) {
            $query->whereHas('entrepreneurProfile', fn (Builder $profile) => $profile->where('province', $filters['province']));
        }

        if (! empty($filters['profile_status'])) {
            $this->applyProfileStatus($query, (string) $filters['profile_status']);
        }

        if (! empty($filters['account_status'])) {
            $this->applyAccountStatus($query, (string) $filters['account_status']);
        }

        $this->applySearch($query, $filters['search'] ?? null, ['name', 'email']);

        return $query;
    }

    private function applyProfileStatus(Builder $query, string $status): void
    {
        if ($status === 'complete') {
            $query->where(function (Builder $query) {
                $query->whereHas('entrepreneurProfile', fn (Builder $profile) => $profile->where('onboarding_completed', true))
                    ->orWhereHas('investorProfile', fn (Builder $profile) => $profile->where('onboarding_completed', true));
            });

            return;
        }

        $query->where(function (Builder $query) {
            $query->whereHas('entrepreneurProfile', fn (Builder $profile) => $profile->where('onboarding_completed', false))
                ->orWhereHas('investorProfile', fn (Builder $profile) => $profile->where('onboarding_completed', false));
        })->whereDoesntHave('entrepreneurProfile', fn (Builder $profile) => $profile->where('onboarding_completed', true))
            ->whereDoesntHave('investorProfile', fn (Builder $profile) => $profile->where('onboarding_completed', true));
    }

    private function profilesQuery(Builder $base): Builder
    {
        return (clone $base)->where(function (Builder $query) {
            $query->whereHas('entrepreneurProfile')->orWhereHas('investorProfile');
        });
    }

    private function completeProfilesQuery(Builder $base): Builder
    {
        return (clone $base)->where(function (Builder $query) {
            $query->whereHas('entrepreneurProfile', fn (Builder $profile) => $profile->where('onboarding_completed', true))
                ->orWhereHas('investorProfile', fn (Builder $profile) => $profile->where('onboarding_completed', true));
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function registrationsChart(StatisticsPeriod $period, array $filters): array
    {
        $series = [];
        $definitions = [
            'total' => ['label' => 'Total', 'color' => 'chart-1'],
            'entrepreneur' => ['label' => 'Entrepreneurs', 'color' => 'chart-3'],
            'investor' => ['label' => 'Investisseurs', 'color' => 'chart-2'],
        ];

        $query = $this->baseQuery($filters)->getQuery();
        $expression = $this->series->dateExpression($query, 'users.created_at', $period);

        foreach (array_keys($definitions) as $role) {
            $roleQuery = $this->applyPeriod($this->baseQuery($filters), 'created_at', $period);

            if ($role !== 'total') {
                $roleQuery->role($role);
            }

            $rows = $roleQuery
                ->selectRaw("{$expression} as bucket, count(*) as total")
                ->groupBy('bucket')
                ->get()
                ->toArray();

            $series[$role] = $this->series->fill($rows, $period);
        }

        return [
            'key' => 'registrations',
            'type' => 'line',
            'title' => 'Évolution des inscriptions',
            'description' => 'Nouveaux comptes par '.($period->granularity === 'month' ? 'mois' : 'jour').'.',
            'data' => $this->series->merge($series, $period),
            'series' => collect($definitions)->map(fn (array $definition, string $key) => [
                'key' => $key,
                'label' => $definition['label'],
                'color' => $definition['color'],
            ])->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function categoryChart(array $filters): array
    {
        $items = [];

        foreach (self::ROLE_ORDER as $index => $role) {
            $count = $this->baseQuery($filters)->role($role)->count();

            if ($count > 0) {
                $items[] = [
                    'name' => self::ROLE_LABELS[$role],
                    'value' => $count,
                    'color' => 'chart-'.(($index % 5) + 1),
                ];
            }
        }

        return [
            'key' => 'categories',
            'type' => 'pie',
            'title' => 'Répartition par catégorie',
            'description' => 'Comptes par rôle principal.',
            'data' => $items,
            'series' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function activeChart(StatisticsPeriod $period, array $filters): array
    {
        $query = $this->baseQuery($filters)->getQuery();
        $expression = $this->series->dateExpression($query, 'users.last_active_at', $period);

        $rows = $this->applyPeriod($this->baseQuery($filters), 'last_active_at', $period)
            ->whereNotNull('last_active_at')
            ->selectRaw("{$expression} as bucket, count(*) as total")
            ->groupBy('bucket')
            ->get()
            ->toArray();

        return [
            'key' => 'active',
            'type' => 'area',
            'title' => 'Évolution des utilisateurs actifs',
            'description' => 'Comptes dont la dernière activité enregistrée tombe dans la période.',
            'data' => $this->series->merge(['active' => $this->series->fill($rows, $period)], $period),
            'series' => [['key' => 'active', 'label' => 'Actifs', 'color' => 'chart-1']],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function profileCompletionChart(StatisticsPeriod $period, array $filters): array
    {
        $base = $this->baseQuery($filters);

        $entrepreneurs = $this->profileSeries(
            EntrepreneurProfile::query(),
            $base,
            $period,
            'entrepreneur_profiles.updated_at',
        );
        $investors = $this->profileSeries(
            InvestorProfile::query(),
            $base,
            $period,
            'investor_profiles.updated_at',
        );

        return [
            'key' => 'profile_completion',
            'type' => 'line',
            'title' => 'Évolution des profils complétés',
            'description' => 'Profils marqués complets, datés par leur dernière mise à jour.',
            'data' => $this->series->merge(['entrepreneurs' => $entrepreneurs, 'investors' => $investors], $period),
            'series' => [
                ['key' => 'entrepreneurs', 'label' => 'Entrepreneurs', 'color' => 'chart-3'],
                ['key' => 'investors', 'label' => 'Investisseurs', 'color' => 'chart-2'],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function profileDistributionChart(array $filters): array
    {
        $base = $this->baseQuery($filters);
        $total = $this->profilesQuery($base)->count();
        $complete = $this->completeProfilesQuery($base)->count();

        $items = [];

        if ($complete > 0) {
            $items[] = ['name' => 'Complets', 'value' => $complete, 'color' => 'chart-3'];
        }

        if ($total - $complete > 0) {
            $items[] = ['name' => 'Incomplets', 'value' => $total - $complete, 'color' => 'chart-5'];
        }

        return [
            'key' => 'profile_distribution',
            'type' => 'pie',
            'title' => 'Complétude des profils',
            'description' => 'Profils entrepreneur et investisseur.',
            'data' => $items,
            'series' => [],
        ];
    }

    /**
     * @param  Builder  $query  profile query
     * @param  Builder  $base  filtered user query
     * @return array<int, array{bucket: string, label: string, value: int|float}>
     */
    private function profileSeries(Builder $query, Builder $base, StatisticsPeriod $period, string $dateColumn): array
    {
        $builder = $query->getQuery();
        $expression = $this->series->dateExpression($builder, $dateColumn, $period);

        $rows = $query
            ->whereIn('user_id', (clone $base)->select('id'))
            ->where('onboarding_completed', true)
            ->whereBetween($dateColumn, [$period->startUtc(), $period->endUtc()])
            ->selectRaw("{$expression} as bucket, count(*) as total")
            ->groupBy('bucket')
            ->get()
            ->toArray();

        return $this->series->fill($rows, $period);
    }

    /** @return array<string, mixed> */
    private function mapRow(User $user): array
    {
        $window = CarbonImmutable::now()->subDays((int) config('statistics.activity_window_days', 30));
        $lastActive = $this->latestActivity($user);
        $role = $user->roles->first()?->name;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'type' => $role !== null ? (self::ROLE_LABELS[$role] ?? $role) : '—',
            'created_at' => $user->created_at?->toIso8601String(),
            'last_active_at' => $lastActive?->toIso8601String(),
            'status' => $lastActive !== null && $lastActive->greaterThanOrEqualTo($window) ? 'active' : 'inactive',
            'status_label' => $lastActive !== null && $lastActive->greaterThanOrEqualTo($window) ? 'Actif' : 'Inactif',
            'projects_count' => (int) ($user->projects_count ?? 0),
            'investments_count' => (int) ($user->investments_count ?? 0),
            'financed_projects_count' => (int) ($user->financed_projects_count ?? 0),
            'profile_complete' => (bool) ($user->entrepreneurProfile?->onboarding_completed || $user->investorProfile?->onboarding_completed),
            'profile_label' => ($user->entrepreneurProfile?->onboarding_completed || $user->investorProfile?->onboarding_completed) ? 'Complet' : 'Incomplet',
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, string>
     */
    protected function filterLabels(array $filters): array
    {
        $labels = [];

        if (! empty($filters['account_type'])) {
            $labels['Type de compte'] = self::ROLE_LABELS[$filters['account_type']] ?? (string) $filters['account_type'];
        }

        if (! empty($filters['account_status'])) {
            $labels['Statut du compte'] = $filters['account_status'] === 'active' ? 'Actifs' : 'Inactifs';
        }

        if (! empty($filters['profile_status'])) {
            $labels['Profil'] = $filters['profile_status'] === 'complete' ? 'Complets' : 'Incomplets';
        }

        if (! empty($filters['province'])) {
            $province = DrcProvince::tryFrom((string) $filters['province']);
            $labels['Province'] = $province?->label() ?? (string) $filters['province'];
        }

        if (! empty($filters['search'])) {
            $labels['Recherche'] = (string) $filters['search'];
        }

        return $labels;
    }
}
