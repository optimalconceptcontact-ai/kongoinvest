<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DrcProvince;
use App\Http\Requests\Admin\EntrepreneursStatisticsRequest;
use App\Http\Requests\Admin\StatisticsRequest;
use App\Models\Sector;
use App\Models\Stage;
use App\Services\AuditLogger;
use App\Services\Statistics\EntrepreneursStatisticsService;
use App\Services\Statistics\Exports\StatisticsCsvExporter;
use App\Services\Statistics\Exports\StatisticsExcelExporter;
use App\Services\Statistics\Exports\StatisticsPdfExporter;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EntrepreneursStatisticsController extends StatisticsPageController
{
    public function __construct(
        EntrepreneursStatisticsService $statistics,
        AuditLogger $audit,
    ) {
        parent::__construct($statistics, $audit);
    }

    public function index(EntrepreneursStatisticsRequest $request): Response
    {
        return $this->page($request);
    }

    public function export(
        EntrepreneursStatisticsRequest $request,
        string $format,
        StatisticsCsvExporter $csv,
        StatisticsExcelExporter $excel,
        StatisticsPdfExporter $pdf,
    ): SymfonyResponse|StreamedResponse {
        return $this->download($request, $format, $csv, $excel, $pdf);
    }

    protected function component(): string
    {
        return 'Admin/Statistics/Entrepreneurs';
    }

    protected function path(): string
    {
        return '/admin/statistics/entrepreneurs';
    }

    protected function note(): string
    {
        return 'Les profils et projets décrivent la situation actuelle ; les inscriptions et l\'activité couvrent la période sélectionnée. Les projets portés incluent tous les statuts, y compris les brouillons.';
    }

    /** @return array<string, mixed> */
    protected function options(StatisticsRequest $request): array
    {
        return [
            'search_placeholder' => 'Nom ou e-mail',
            'filters' => [
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
                    'key' => 'onboarding',
                    'label' => 'Profil',
                    'all_label' => 'Tous',
                    'options' => [
                        ['value' => 'complete', 'label' => 'Complets'],
                        ['value' => 'incomplete', 'label' => 'Incomplets'],
                    ],
                ],
                [
                    'key' => 'account_status',
                    'label' => 'Statut du compte',
                    'all_label' => 'Tous',
                    'options' => [
                        ['value' => 'active', 'label' => 'Actifs'],
                        ['value' => 'inactive', 'label' => 'Inactifs'],
                    ],
                ],
            ],
        ];
    }
}
