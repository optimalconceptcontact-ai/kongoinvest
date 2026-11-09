<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentProviderName;
use App\Enums\PaymentStatus;
use App\Http\Requests\Admin\RevenueStatisticsRequest;
use App\Http\Requests\Admin\StatisticsRequest;
use App\Models\SubscriptionPlan;
use App\Services\AuditLogger;
use App\Services\Statistics\Exports\StatisticsCsvExporter;
use App\Services\Statistics\Exports\StatisticsExcelExporter;
use App\Services\Statistics\Exports\StatisticsPdfExporter;
use App\Services\Statistics\RevenueStatisticsService;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RevenueStatisticsController extends StatisticsPageController
{
    public function __construct(
        RevenueStatisticsService $statistics,
        AuditLogger $audit,
    ) {
        parent::__construct($statistics, $audit);
    }

    public function index(RevenueStatisticsRequest $request): Response
    {
        return $this->page($request);
    }

    public function export(
        RevenueStatisticsRequest $request,
        string $format,
        StatisticsCsvExporter $csv,
        StatisticsExcelExporter $excel,
        StatisticsPdfExporter $pdf,
    ): SymfonyResponse|StreamedResponse {
        return $this->download($request, $format, $csv, $excel, $pdf);
    }

    protected function component(): string
    {
        return 'Admin/Statistics/Revenue';
    }

    protected function path(): string
    {
        return '/admin/statistics/revenue';
    }

    protected function note(): string
    {
        return 'Périmètre : paiements d\'abonnement enregistrés dans le grand livre des paiements. Les factures Stripe/Cashier non encore miroitées et les fonds d\'escrow ne sont pas inclus. Le revenu récurrent contracté est calculé au prorata mensuel des abonnements actifs.';
    }

    /** @return array<string, mixed> */
    protected function options(StatisticsRequest $request): array
    {
        return [
            'search_placeholder' => 'Payeur ou référence',
            'filters' => [
                [
                    'key' => 'status',
                    'label' => 'Statut',
                    'all_label' => 'Tous',
                    'options' => $this->enumOptions(PaymentStatus::class),
                ],
                [
                    'key' => 'provider',
                    'label' => 'Fournisseur',
                    'all_label' => 'Tous',
                    'options' => $this->enumOptions(PaymentProviderName::class),
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
                [
                    'key' => 'plan_id',
                    'label' => 'Plan',
                    'all_label' => 'Tous',
                    'options' => SubscriptionPlan::query()
                        ->orderBy('order')
                        ->get(['id', 'name'])
                        ->map(fn (SubscriptionPlan $plan) => ['value' => (string) $plan->id, 'label' => $plan->name])
                        ->all(),
                ],
            ],
        ];
    }
}
