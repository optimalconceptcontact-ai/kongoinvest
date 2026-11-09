<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EscrowStatus;
use App\Http\Requests\Admin\InvestmentsStatisticsRequest;
use App\Http\Requests\Admin\StatisticsRequest;
use App\Services\AuditLogger;
use App\Services\Statistics\Exports\StatisticsCsvExporter;
use App\Services\Statistics\Exports\StatisticsExcelExporter;
use App\Services\Statistics\Exports\StatisticsPdfExporter;
use App\Services\Statistics\InvestmentsStatisticsService;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvestmentsStatisticsController extends StatisticsPageController
{
    public function __construct(
        InvestmentsStatisticsService $statistics,
        AuditLogger $audit,
    ) {
        parent::__construct($statistics, $audit);
    }

    public function index(InvestmentsStatisticsRequest $request): Response
    {
        return $this->page($request);
    }

    public function export(
        InvestmentsStatisticsRequest $request,
        string $format,
        StatisticsCsvExporter $csv,
        StatisticsExcelExporter $excel,
        StatisticsPdfExporter $pdf,
    ): SymfonyResponse|StreamedResponse {
        return $this->download($request, $format, $csv, $excel, $pdf);
    }

    protected function component(): string
    {
        return 'Admin/Statistics/Investments';
    }

    protected function path(): string
    {
        return '/admin/statistics/investments';
    }

    protected function note(): string
    {
        return 'Seuls les escrows matérialisent un investissement réalisé. Les demandes de connexion et les offres de financement sont un pipeline et ne sont jamais comptées comme investissements. Les fonds d\'escrow ne sont jamais du revenu.';
    }

    /** @return array<string, mixed> */
    protected function options(StatisticsRequest $request): array
    {
        return [
            'search_placeholder' => 'Projet, investisseur ou entrepreneur',
            'filters' => [
                [
                    'key' => 'status',
                    'label' => 'Statut',
                    'all_label' => 'Tous',
                    'options' => $this->enumOptions(EscrowStatus::class),
                ],
                [
                    'key' => 'currency',
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
