<?php

namespace App\Services\Statistics;

use App\Enums\DrcProvince;
use App\Enums\ProjectStatus;
use App\Enums\ProjectVisibility;
use App\Models\Project;
use App\Models\Sector;
use App\Models\Stage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProjectsStatisticsService extends StatisticsService
{
    public const KEY = 'projects';

    /** @var array<int, string> */
    private const SORTABLE = ['title', 'created_at', 'published_at', 'funding_target_minor'];

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function kpis(StatisticsPeriod $period, array $filters): array
    {
        $base = $this->baseQuery($filters);

        $total = (clone $base)->count();
        $published = (clone $base)->where('status', ProjectStatus::Published->value)->count();
        $draft = (clone $base)->where('status', ProjectStatus::Draft->value)->count();
        $pendingReview = (clone $base)->where('status', ProjectStatus::PendingReview->value)->count();
        $rejected = (clone $base)->where('status', ProjectStatus::Rejected->value)->count();
        $archived = (clone $base)->where('status', ProjectStatus::Archived->value)->count();

        $new = $this->applyPeriod(clone $base, 'created_at', $period)->count();
        $previousNew = $this->applyPreviousPeriod(clone $base, 'created_at', $period)->count();

        $publishedNow = $this->applyPeriod(clone $base, 'published_at', $period)
            ->whereNotNull('published_at')
            ->count();
        $publishedBefore = $this->applyPreviousPeriod(clone $base, 'published_at', $period)
            ->whereNotNull('published_at')
            ->count();

        $withFinancingRequest = (clone $base)->has('financingRequests')->count();

        $targets = $this->totalsByCurrency(
            (clone $base)->whereNotNull('funding_target_minor'),
            'funding_target_minor',
            'funding_currency',
        );

        return array_merge([
            $this->kpi('total', 'Projets', $total, 'integer', hint: 'Tous statuts confondus'),
            $this->kpi('published', 'Projets publiés', $published, 'integer'),
            $this->kpi('draft', 'Brouillons', $draft, 'integer'),
            $this->kpi('pending_review', 'En attente de validation', $pendingReview, 'integer'),
            $this->kpi('rejected', 'Rejetés', $rejected, 'integer'),
            $this->kpi('archived', 'Archivés', $archived, 'integer'),
            $this->kpi('new', 'Nouveaux projets', $new, 'integer', $this->variation($new, $previousNew), $period->previousLabel),
            $this->kpi('published_on_period', 'Publiés sur la période', $publishedNow, 'integer', $this->variation($publishedNow, $publishedBefore), $period->previousLabel),
            $this->kpi('with_financing_request', 'Avec demande de financement', $withFinancingRequest, 'integer'),
        ], $this->currencyKpis(
            'target',
            'Objectif de financement',
            $targets,
            hint: 'Objectifs déclarés par les porteurs',
        ));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function charts(StatisticsPeriod $period, array $filters): array
    {
        return [
            $this->creationsChart($period, $filters),
            $this->statusChart($filters),
            $this->visibilityChart($filters),
            $this->sectorChart($filters),
            $this->provinceChart($filters),
            $this->currencyChart($filters),
        ];
    }

    /** @param array<string, mixed> $filters */
    public function tableQuery(array $filters): Builder
    {
        return $this->baseQuery($filters)
            ->with(['user', 'sectors', 'stages'])
            ->withCount('financingRequests');
    }

    /** @param array<string, mixed> $filters */
    public function paginatedTable(array $filters): LengthAwarePaginator
    {
        return $this->paginate(
            $this->tableQuery($filters),
            $filters,
            self::SORTABLE,
            'created_at',
        )->through(fn (Project $project) => $this->mapRow($project));
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
            ->map(fn (Project $project) => $this->mapRow($project));
    }

    /** @return array<int, array<string, mixed>> */
    public function tableColumns(): array
    {
        return [
            ['key' => 'title', 'label' => 'Projet', 'sortable' => true, 'type' => 'text'],
            ['key' => 'entrepreneur', 'label' => 'Entrepreneur', 'sortable' => false, 'type' => 'text'],
            ['key' => 'status', 'label' => 'Statut', 'sortable' => false, 'type' => 'badge'],
            ['key' => 'visibility', 'label' => 'Visibilité', 'sortable' => false, 'type' => 'text'],
            ['key' => 'sectors', 'label' => 'Secteurs', 'sortable' => false, 'type' => 'text'],
            ['key' => 'provinces', 'label' => 'Provinces', 'sortable' => false, 'type' => 'text'],
            ['key' => 'funding_target', 'label' => 'Objectif', 'sortable' => true, 'type' => 'currency', 'currency_key' => 'funding_currency'],
            ['key' => 'financing_requests_count', 'label' => 'Demandes', 'sortable' => false, 'type' => 'number'],
            ['key' => 'created_at', 'label' => 'Création', 'sortable' => true, 'type' => 'date'],
            ['key' => 'published_at', 'label' => 'Publication', 'sortable' => true, 'type' => 'date'],
        ];
    }

    protected function title(): string
    {
        return 'Analyse des projets';
    }

    protected function description(): string
    {
        return 'Portefeuille de projets, statuts, secteurs, couverture géographique et objectifs de financement.';
    }

    /** @param array<string, mixed> $filters */
    private function baseQuery(array $filters): Builder
    {
        $query = Project::query();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['visibility'])) {
            $query->where('visibility', $filters['visibility']);
        }

        if (! empty($filters['province'])) {
            $query->whereJsonContains('provinces', (string) $filters['province']);
        }

        if (! empty($filters['sector_id'])) {
            $query->whereHas('sectors', fn (Builder $sectors) => $sectors->where('sectors.id', $filters['sector_id']));
        }

        if (! empty($filters['stage_id'])) {
            $query->whereHas('stages', fn (Builder $stages) => $stages->where('stages.id', $filters['stage_id']));
        }

        if (! empty($filters['funding_currency'])) {
            $query->where('funding_currency', strtoupper((string) $filters['funding_currency']));
        }

        if (! empty($filters['search'])) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim((string) $filters['search'])).'%';

            $query->where(function (Builder $search) use ($term) {
                $search->where('title', 'like', $term)
                    ->orWhere('slug', 'like', $term)
                    ->orWhereHas('user', fn (Builder $user) => $user->where('name', 'like', $term)->orWhere('email', 'like', $term));
            });
        }

        return $query;
    }

    /** @param array<string, mixed> $filters */
    private function creationsChart(StatisticsPeriod $period, array $filters): array
    {
        return $this->lineChart(
            'creations',
            'line',
            'Évolution des projets',
            'Créations et publications par '.($period->granularity === 'month' ? 'mois' : 'jour').'.',
            [
                'created' => $this->bucketCounts($this->baseQuery($filters), 'projects.created_at', $period),
                'published' => $this->bucketCounts(
                    $this->baseQuery($filters)->whereNotNull('projects.published_at'),
                    'projects.published_at',
                    $period,
                ),
            ],
            [
                'created' => ['label' => 'Créés', 'color' => 'chart-1'],
                'published' => ['label' => 'Publiés', 'color' => 'chart-2'],
            ],
            $period,
        );
    }

    /** @param array<string, mixed> $filters */
    private function statusChart(array $filters): array
    {
        $rows = (clone $this->baseQuery($filters))
            ->selectRaw('status as name, count(*) as total')
            ->groupBy('status')
            ->get();

        $labels = [];
        $counts = [];

        foreach ($rows as $row) {
            $status = ProjectStatus::tryFrom((string) $row->name);
            $labels[(string) $row->name] = $status?->label() ?? (string) $row->name;
            $counts[(string) $row->name] = (int) $row->total;
        }

        return $this->pieChart(
            'statuses',
            'Répartition par statut',
            'État courant des projets filtrés.',
            $this->pieItems($counts, $labels),
        );
    }

    /** @param array<string, mixed> $filters */
    private function visibilityChart(array $filters): array
    {
        $rows = (clone $this->baseQuery($filters))
            ->selectRaw('visibility as name, count(*) as total')
            ->groupBy('visibility')
            ->get();

        $labels = [];
        $counts = [];

        foreach ($rows as $row) {
            $visibility = ProjectVisibility::tryFrom((string) $row->name);
            $labels[(string) $row->name] = $visibility?->label() ?? (string) $row->name;
            $counts[(string) $row->name] = (int) $row->total;
        }

        return $this->pieChart(
            'visibilities',
            'Répartition par visibilité',
            'Niveau d\'ouverture des projets.',
            $this->pieItems($counts, $labels),
        );
    }

    /** @param array<string, mixed> $filters */
    private function sectorChart(array $filters): array
    {
        $rows = DB::table('project_sector')
            ->join('sectors', 'sectors.id', '=', 'project_sector.sector_id')
            ->whereIn('project_sector.project_id', (clone $this->baseQuery($filters))->select('id'))
            ->selectRaw('sectors.name as name, count(*) as total')
            ->groupBy('sectors.name')
            ->get();

        $counts = [];

        foreach ($rows as $row) {
            $counts[(string) $row->name] = (int) $row->total;
        }

        return $this->pieChart(
            'sectors',
            'Répartition sectorielle',
            'Secteurs associés aux projets filtrés.',
            $this->pieItems($counts, limit: 8),
        );
    }

    /** @param array<string, mixed> $filters */
    private function provinceChart(array $filters): array
    {
        $rows = (clone $this->baseQuery($filters))
            ->select('provinces')
            ->get();

        $counts = [];

        foreach ($rows as $row) {
            foreach ((array) ($row->provinces ?? []) as $province) {
                $enum = DrcProvince::tryFrom((string) $province);
                $label = $enum?->label() ?? (string) $province;
                $counts[$label] = ($counts[$label] ?? 0) + 1;
            }
        }

        return $this->pieChart(
            'provinces',
            'Couverture géographique',
            'Provinces déclarées par les projets (un projet peut en couvrir plusieurs).',
            $this->pieItems($counts, limit: 10),
        );
    }

    /** @param array<string, mixed> $filters */
    private function currencyChart(array $filters): array
    {
        $rows = (clone $this->baseQuery($filters))
            ->selectRaw('funding_currency as name, count(*) as total')
            ->groupBy('funding_currency')
            ->get();

        $counts = [];

        foreach ($rows as $row) {
            $counts[strtoupper((string) $row->name)] = (int) $row->total;
        }

        return $this->pieChart(
            'funding_currencies',
            'Devises de financement',
            'Devise déclarée pour l\'objectif de financement.',
            $this->pieItems($counts),
        );
    }

    /** @return array<string, mixed> */
    private function mapRow(Project $project): array
    {
        $sectors = $project->sectors->pluck('name')->all();
        $provinces = [];

        foreach ((array) ($project->provinces ?? []) as $province) {
            $enum = DrcProvince::tryFrom((string) $province);
            $provinces[] = $enum?->label() ?? (string) $province;
        }

        $status = $project->status;
        $visibility = $project->visibility;

        return [
            'id' => $project->id,
            'title' => $project->title,
            'entrepreneur' => $project->user?->name ?? '—',
            'status' => $status instanceof ProjectStatus ? $status->value : (string) $status,
            'status_label' => $status instanceof ProjectStatus ? $status->label() : (string) $status,
            'visibility' => $visibility instanceof ProjectVisibility ? $visibility->label() : (string) $visibility,
            'sectors' => $sectors === [] ? '—' : implode(', ', $sectors),
            'provinces' => $provinces === [] ? '—' : implode(', ', $provinces),
            'funding_target' => $project->funding_target_minor !== null ? round($project->funding_target_minor / 100, 2) : null,
            'funding_currency' => strtoupper((string) $project->funding_currency),
            'financing_requests_count' => (int) ($project->financing_requests_count ?? 0),
            'created_at' => $project->created_at?->toIso8601String(),
            'published_at' => $project->published_at?->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, string>
     */
    protected function filterLabels(array $filters): array
    {
        $labels = [];

        if (! empty($filters['status'])) {
            $labels['Statut'] = ProjectStatus::tryFrom((string) $filters['status'])?->label() ?? (string) $filters['status'];
        }

        if (! empty($filters['visibility'])) {
            $labels['Visibilité'] = ProjectVisibility::tryFrom((string) $filters['visibility'])?->label() ?? (string) $filters['visibility'];
        }

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

        if (! empty($filters['funding_currency'])) {
            $labels['Devise'] = strtoupper((string) $filters['funding_currency']);
        }

        if (! empty($filters['search'])) {
            $labels['Recherche'] = (string) $filters['search'];
        }

        return $labels;
    }
}
