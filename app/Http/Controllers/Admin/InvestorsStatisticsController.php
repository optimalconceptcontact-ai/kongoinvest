<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FundingType;
use App\Enums\InvestmentHorizon;
use App\Enums\InvestorType;
use App\Enums\RiskAppetite;
use App\Http\Requests\Admin\InvestorsStatisticsRequest;
use App\Http\Requests\Admin\StatisticsRequest;
use App\Services\AuditLogger;
use App\Services\Statistics\Exports\StatisticsCsvExporter;
use App\Services\Statistics\Exports\StatisticsExcelExporter;
use App\Services\Statistics\Exports\StatisticsPdfExporter;
use App\Services\Statistics\InvestorsStatisticsService;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvestorsStatisticsController extends StatisticsPageController
{
    public function __construct(
        InvestorsStatisticsService $statistics,
        AuditLogger $audit,
    ) {
        parent::__construct($statistics, $audit);
    }

    public function index(InvestorsStatisticsRequest $request): Response
    {
        return $this->page($request);
    }

    public function export(
        InvestorsStatisticsRequest $request,
        string $format,
        StatisticsCsvExporter $csv,
        StatisticsExcelExporter $excel,
        StatisticsPdfExporter $pdf,
    ): SymfonyResponse|StreamedResponse {
        return $this->download($request, $format, $csv, $excel, $pdf);
    }

    protected function component(): string
    {
        return 'Admin/Statistics/Investors';
    }

    protected function path(): string
    {
        return '/admin/statistics/investors';
    }

    protected function note(): string
    {
        return 'Le capital investi provient uniquement des escrows financés, hors remboursés et annulés ; les offres de financement restent un pipeline non réalisé. Les montants de devises différentes ne sont jamais additionnés.';
    }

    /** @return array<string, mixed> */
    protected function options(StatisticsRequest $request): array
    {
        return [
            'search_placeholder' => 'Nom ou e-mail',
            'filters' => [
                [
                    'key' => 'country_code',
                    'label' => 'Pays (code ISO)',
                    'type' => 'text',
                    'placeholder' => 'CD',
                    'max_length' => 2,
                ],
                [
                    'key' => 'investor_type',
                    'label' => 'Type d\'investisseur',
                    'all_label' => 'Tous',
                    'options' => $this->enumOptions(InvestorType::class),
                ],
                [
                    'key' => 'funding_type',
                    'label' => 'Instrument',
                    'all_label' => 'Tous',
                    'options' => $this->enumOptions(FundingType::class),
                ],
                [
                    'key' => 'risk_appetite',
                    'label' => 'Appétence au risque',
                    'all_label' => 'Toutes',
                    'options' => $this->enumOptions(RiskAppetite::class),
                ],
                [
                    'key' => 'investment_horizon',
                    'label' => 'Horizon',
                    'all_label' => 'Tous',
                    'options' => $this->enumOptions(InvestmentHorizon::class),
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
