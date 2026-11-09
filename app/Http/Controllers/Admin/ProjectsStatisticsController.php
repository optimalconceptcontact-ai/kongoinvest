<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DrcProvince;
use App\Enums\ProjectStatus;
use App\Enums\ProjectVisibility;
use App\Http\Requests\Admin\ProjectsStatisticsRequest;
use App\Http\Requests\Admin\StatisticsRequest;
use App\Models\Sector;
use App\Models\Stage;
use App\Services\AuditLogger;
use App\Services\Statistics\Exports\StatisticsCsvExporter;
use App\Services\Statistics\Exports\StatisticsExcelExporter;
use App\Services\Statistics\Exports\StatisticsPdfExporter;
use App\Services\Statistics\ProjectsStatisticsService;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectsStatisticsController extends StatisticsPageController
{
    public function __construct(
        ProjectsStatisticsService $statistics,
        AuditLogger $audit,
    ) {
        parent::__construct($statistics, $audit);
    }

    public function index(ProjectsStatisticsRequest $request): Response
    {
        return $this->page($request);
    }

    public function export(
        ProjectsStatisticsRequest $request,
        string $format,
        StatisticsCsvExporter $csv,
        StatisticsExcelExporter $excel,
        StatisticsPdfExporter $pdf,
    ): SymfonyResponse|StreamedResponse {
        return $this->download($request, $format, $csv, $excel, $pdf);
    }

    protected function component(): string
    {
        return 'Admin/Statistics/Projects';
    }

    protected function path(): string
    {
        return '/admin/statistics/projects';
    }

    protected function note(): string
    {
        return 'Les objectifs de financement sont les montants déclarés par les porteurs, jamais les montants effectivement levés. Un projet peut couvrir plusieurs provinces, chaque province est donc comptée séparément dans le graphique géographique.';
    }

    /** @return array<string, mixed> */
    protected function options(StatisticsRequest $request): array
    {
        return [
            'search_placeholder' => 'Titre, entrepreneur ou e-mail',
            'filters' => [
                [
                    'key' => 'status',
                    'label' => 'Statut',
                    'all_label' => 'Tous',
                    'options' => $this->enumOptions(ProjectStatus::class),
                ],
                [
                    'key' => 'visibility',
                    'label' => 'Visibilité',
                    'all_label' => 'Toutes',
                    'options' => $this->enumOptions(ProjectVisibility::class),
                ],
                [
                    'key' => 'province',
                    'label' => 'Province',
                    'all_label' => 'Toutes',
                    'options' => array_map(
                        fn (DrcProvince $province) => ['value' => $province->value, 'label' => $province->label()],
                        DrcProvince::cases(),
                    ),
                ],
                [
                    'key' => 'sector_id',
                    'label' => 'Secteur',
                    'all_label' => 'Tous',
                    'options' => Sector::query()
                        ->where('is_active', true)
                        ->orderBy('order')
                        ->get(['id', 'name'])
                        ->map(fn (Sector $sector) => ['value' => (string) $sector->id, 'label' => $sector->name])
                        ->all(),
                ],
                [
                    'key' => 'stage_id',
                    'label' => 'Stade',
                    'all_label' => 'Tous',
                    'options' => Stage::query()
                        ->where('is_active', true)
                        ->orderBy('order')
                        ->get(['id', 'name'])
                        ->map(fn (Stage $stage) => ['value' => (string) $stage->id, 'label' => $stage->name])
                        ->all(),
                ],
                [
                    'key' => 'funding_currency',
                    'label' => 'Devise',
                    'all_label' => 'Toutes',
                    'options' => [
                        ['value' => 'USD', 'label' => 'USD'],
                        ['value' => 'CDF', 'label' => 'CDF'],
                    ],
                ],
            ],
        ];
    }
}
