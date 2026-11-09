<?php

namespace App\Services\Statistics;

use App\Enums\DrcProvince;
use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\Sector;
use App\Models\Stage;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EntrepreneursStatisticsService extends StatisticsService
{
    public const KEY = 'entrepreneurs';

    /** @var array<int, string> */
    private const SORTABLE = ['name', 'email', 'created_at', 'last_active_at'];

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function kpis(StatisticsPeriod $period, array $filters): array
    {
        $base = $this->baseQuery($filters);

        $total = (clone $base)->count();
        $new = $this->applyPeriod(clone $base, 'created_at', $period)->count();
        $previousNew = $this->applyPreviousPeriod(clone $base, 'created_at', $period)->count();
        $active = $this->activeInPeriod(clone $base, $period)->count();
        $previousActive = $this->activeInPeriod(clone $base, $period, previous: true)->count();

        $onboardingComplete = (clone $base)
            ->whereHas('entrepreneurProfile', fn (Builder $query) => $query->where('onboarding_completed', true))
            ->count();

        $withProjects = (clone $base)->has('projects')->count();

        $withPublishedProject = (clone $base)
            ->whereHas('projects', fn (Builder $query) => $query->where('status', ProjectStatus::Published->value))
            ->count();

        $projects = Project::query()
            ->whereIn('user_id', (clone $base)->select('id'))
            ->count();

        $provinces = DB::table('entrepreneur_profiles')
            ->whereIn('user_id', (clone $base)->select('id'))
            ->whereNotNull('province')
            ->distinct()
            ->count('province');

        return [
            $this->kpi('total', 'Entrepreneurs', $total, 'integer', hint: 'Comptes portant le rôle entrepreneur'),
            $this->kpi('new', 'Nouveaux entrepreneurs', $new, 'integer', $this->variation($new, $previousNew), $period->previousLabel),
            $this->kpi('active', 'Actifs', $active, 'integer', $this->variation($active, $previousActive), $period->previousLabel, 'Activité enregistrée sur la période'),
            $this->kpi('onboarding_complete', 'Profils complétés', $onboardingComplete, 'integer', hint: 'Onboarding entrepreneur terminé'),
            $this->kpi('with_projects', 'Avec au moins un projet', $withProjects, 'integer'),
            $this->kpi('with_published_project', 'Avec un projet publié', $withPublishedProject, 'integer'),
            $this->kpi('projects', 'Projets portés', $projects, 'integer', hint: 'Tous statuts confondus'),
            $this->kpi('provinces', 'Provinces représentées', $provinces, 'integer'),
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
            $this->provinceChart($filters),
            $this->sectorChart($filters),
            $this->stageChart($filters),
            $this->onboardingChart($filters),
        ];
    }

    /** @param array<string, mixed> $filters */
    public function tableQuery(array $filters): Builder
    {
        return $this->baseQuery($filters)
            ->with('entrepreneurProfile.company')
            ->withCount([
                'projects',
                'projects as published_projects_count' => fn (Builder $query) => $query->where('status', ProjectStatus::Published->value),
            ])
            ->withMax('auditLogs as last_audit_at', 'created_at');
    }

    /** @param array<string, mixed> $filters */
    public function paginatedTable(array $filters): LengthAwarePaginator
    {
        return $this->paginate(
            $this->tableQuery($filters),
            $filters,
            self::SORTABLE,
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
        $sort = in_array($filters['sort'] ?? null, self::SORTABLE, true) ? $filters['sort'] : 'created_at';
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $this->tableQuery($filters)
            ->orderBy($sort, $direction)
            ->limit($limit)
            ->get()
            ->map(fn (User $user) => $this->mapRow($user));
    }

    /** @return array<int, array<string, mixed>> */
    public function tableColumns(): array
    {
        return [
            ['key' => 'name', 'label' => 'Nom', 'sortable' => true, 'type' => 'text'],
            ['key' => 'email', 'label' => 'E-mail', 'sortable' => true, 'type' => 'text'],
            ['key' => 'company', 'label' => 'Entreprise', 'sortable' => false, 'type' => 'text'],
            ['key' => 'province', 'label' => 'Province', 'sortable' => false, 'type' => 'text'],
            ['key' => 'projects_count', 'label' => 'Projets', 'sortable' => false, 'type' => 'number'],
            ['key' => 'published_projects_count', 'label' => 'Projets publiés', 'sortable' => false, 'type' => 'number'],
            ['key' => 'onboarding', 'label' => 'Profil', 'sortable' => false, 'type' => 'badge'],
            ['key' => 'last_active_at', 'label' => 'Dernière activité', 'sortable' => true, 'type' => 'date'],
            ['key' => 'created_at', 'label' => 'Inscription', 'sortable' => true, 'type' => 'date'],
        ];
    }

    protected function title(): string
    {
        return 'Analyse des entrepreneurs';
    }

    protected function description(): string
    {
        return 'Portefeuille entrepreneurial, complétude des profils, projets et couverture géographique.';
    }

    /** @param array<string, mixed> $filters */
    private function baseQuery(array $filters): Builder
    {
        $query = User::query()->role('entrepreneur');

        if (! empty($filters['province'])) {
            $query->whereHas('entrepreneurProfile', fn (Builder $profile) => $profile->where('province', $filters['province']));
        }

        if (! empty($filters['onboarding'])) {
            $query->whereHas('entrepreneurProfile', fn (Builder $profile) => $profile->where(
                'onboarding_completed',
                $filters['onboarding'] === 'complete',
            ));
        }

        if (! empty($filters['sector_id'])) {
            $query->whereHas('projects.sectors', fn (Builder $sectors) => $sectors->where('sectors.id', $filters['sector_id']));
        }

        if (! empty($filters['stage_id'])) {
            $query->whereHas('projects.stages', fn (Builder $stages) => $stages->where('stages.id', $filters['stage_id']));
        }

        if (! empty($filters['account_status'])) {
            $this->applyAccountStatus($query, (string) $filters['account_status']);
        }

        $this->applySearch($query, $filters['search'] ?? null, ['name', 'email']);

        return $query;
    }

    /** @param array<string, mixed> $filters */
    private function registrationsChart(StatisticsPeriod $period, array $filters): array
    {
        $base = $this->baseQuery($filters);

        return $this->lineChart(
            'registrations',
            'line',
            'Évolution des inscriptions',
            'Nouveaux comptes entrepreneur par '.($period->granularity === 'month' ? 'mois' : 'jour').'.',
            [
                'total' => $this->bucketCounts($base, 'users.created_at', $period),
                'active' => $this->bucketCounts(
                    $this->activeInPeriod($this->baseQuery($filters), $period, previous: false),
                    'users.last_active_at',
                    $period,
                ),
            ],
            [
                'total' => ['label' => 'Inscriptions', 'color' => 'chart-1'],
                'active' => ['label' => 'Actifs', 'color' => 'chart-2'],
            ],
            $period,
        );
    }

    /** @param array<string, mixed> $filters */
    private function provinceChart(array $filters): array
    {
        $rows = DB::table('entrepreneur_profiles')
            ->whereIn('user_id', (clone $this->baseQuery($filters))->select('id'))
            ->whereNotNull('province')
            ->selectRaw('province as name, count(*) as total')
            ->groupBy('province')
            ->get();

        $counts = [];

        foreach ($rows as $row) {
            $province = DrcProvince::tryFrom((string) $row->name);

            $counts[$province?->label() ?? (string) $row->name] = (int) $row->total;
        }

        return $this->pieChart(
            'provinces',
            'Répartition par province',
            'Entrepreneurs disposant d\'un profil localisé.',
            $this->pieItems($counts),
        );
    }

    /** @param array<string, mixed> $filters */
    private function sectorChart(array $filters): array
    {
        $rows = DB::table('project_sector')
            ->join('projects', 'projects.id', '=', 'project_sector.project_id')
            ->join('sectors', 'sectors.id', '=', 'project_sector.sector_id')
            ->whereIn('projects.user_id', (clone $this->baseQuery($filters))->select('id'))
            ->whereNull('projects.deleted_at')
            ->selectRaw('sectors.name as name, count(*) as total')
            ->groupBy('sectors.name')
            ->get();

        $counts = [];

        foreach ($rows as $row) {
            $counts[(string) $row->name] = (int) $row->total;
        }

        return $this->pieChart(
            'sectors',
            'Répartition sectorielle des projets',
            'Secteurs déclarés par les projets portés.',
            $this->pieItems($counts, limit: 8),
        );
    }

    /** @param array<string, mixed> $filters */
    private function stageChart(array $filters): array
    {
        $rows = DB::table('project_stage')
            ->join('projects', 'projects.id', '=', 'project_stage.project_id')
            ->join('stages', 'stages.id', '=', 'project_stage.stage_id')
            ->whereIn('projects.user_id', (clone $this->baseQuery($filters))->select('id'))
            ->whereNull('projects.deleted_at')
            ->selectRaw('stages.name as name, count(*) as total')
            ->groupBy('stages.name')
            ->get();

        $counts = [];

        foreach ($rows as $row) {
            $counts[(string) $row->name] = (int) $row->total;
        }

        return $this->pieChart(
            'stages',
            'Répartition par stade de maturité',
            'Stades déclarés par les projets portés.',
            $this->pieItems($counts),
        );
    }

    /** @param array<string, mixed> $filters */
    private function onboardingChart(array $filters): array
    {
        $base = $this->baseQuery($filters);
        $total = (clone $base)->count();
        $complete = (clone $base)
            ->whereHas('entrepreneurProfile', fn (Builder $query) => $query->where('onboarding_completed', true))
            ->count();

        return $this->pieChart(
            'onboarding',
            'Complétude des profils entrepreneurs',
            'Profils dont l\'onboarding est terminé.',
            [
                ['name' => 'Complets', 'value' => $complete, 'color' => 'chart-2'],
                ['name' => 'Incomplets', 'value' => max(0, $total - $complete), 'color' => 'chart-5'],
            ],
        );
    }

    /** @return array<string, mixed> */
    private function mapRow(User $user): array
    {
        $lastActive = $this->latestActivity($user);
        $province = $user->entrepreneurProfile?->province;
        $provinceEnum = $province instanceof DrcProvince
            ? $province
            : ($province !== null ? DrcProvince::tryFrom((string) $province) : null);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'company' => $user->entrepreneurProfile?->company?->name ?? '—',
            'province' => $provinceEnum?->label() ?? (is_string($province) ? $province : '—'),
            'projects_count' => (int) ($user->projects_count ?? 0),
            'published_projects_count' => (int) ($user->published_projects_count ?? 0),
            'onboarding' => $user->entrepreneurProfile?->onboarding_completed ? 'complete' : 'incomplete',
            'onboarding_label' => $user->entrepreneurProfile?->onboarding_completed ? 'Complet' : 'Incomplet',
            'last_active_at' => $lastActive?->toIso8601String(),
            'created_at' => $user->created_at?->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, string>
     */
    protected function filterLabels(array $filters): array
    {
        $labels = [];

        if (! empty($filters['province'])) {
            $province = DrcProvince::tryFrom((string) $filters['province']);
            $labels['Province'] = $province?->label() ?? (string) $filters['province'];
        }

        if (! empty($filters['sector_id'])) {
            $labels['Secteur'] = Sector::find($filters['sector_id'])?->name ?? (string) $filters['sector_id'];
        }

        if (! empty($filters['stage_id'])) {
            $labels['Stade'] = Stage::find($filters['stage_id'])?->name ?? (string) $filters['stage_id'];
        }

        if (! empty($filters['onboarding'])) {
            $labels['Profil'] = $filters['onboarding'] === 'complete' ? 'Complets' : 'Incomplets';
        }

        if (! empty($filters['account_status'])) {
            $labels['Statut du compte'] = $filters['account_status'] === 'active' ? 'Actifs' : 'Inactifs';
        }

        if (! empty($filters['search'])) {
            $labels['Recherche'] = (string) $filters['search'];
        }

        return $labels;
    }
}
