<?php

namespace App\Services\Statistics;

use App\Enums\PaymentProviderName;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RevenueStatisticsService extends StatisticsService
{
    public const KEY = 'revenue';

    /** @var array<int, string> */
    private const SORTABLE = ['created_at', 'amount_minor', 'status'];

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function kpis(StatisticsPeriod $period, array $filters): array
    {
        $base = $this->baseQuery($filters);

        $total = (clone $base)->count();
        $succeeded = (clone $base)->where('status', PaymentStatus::Succeeded->value)->count();
        $failed = (clone $base)->where('status', PaymentStatus::Failed->value)->count();
        $pending = (clone $base)->whereIn('status', [
            PaymentStatus::Pending->value,
            PaymentStatus::Processing->value,
        ])->count();
        $refunded = (clone $base)->whereIn('status', [
            PaymentStatus::Refunded->value,
            PaymentStatus::PartiallyRefunded->value,
        ])->count();

        $succeededQuery = (clone $base)->where('status', PaymentStatus::Succeeded->value);
        $revenueTotals = $this->totalsByCurrency(
            $this->applyPeriod(clone $succeededQuery, 'created_at', $period),
        );
        $previousTotals = $this->totalsByCurrency(
            $this->applyPreviousPeriod(clone $succeededQuery, 'created_at', $period),
        );

        $variations = [];

        foreach ($revenueTotals as $currency => $minor) {
            $variations[$currency] = $this->variation($minor, $previousTotals[$currency] ?? 0);
        }

        $activeSubscriptions = Subscription::query()
            ->whereIn('status', [SubscriptionStatus::Active->value, SubscriptionStatus::Trialing->value])
            ->count();

        $mrr = $this->monthlyRecurringRevenue();

        $investmentPayments = Payment::query()
            ->where('purpose', PaymentPurpose::Investment->value)
            ->whereBetween('created_at', [$period->startUtc(), $period->endUtc()])
            ->count();

        return array_merge([
            $this->kpi('total', 'Transactions', $total, 'integer', hint: 'Paiements d\'abonnement enregistrés'),
            $this->kpi('succeeded', 'Réussies', $succeeded, 'integer'),
            $this->kpi('failed', 'Échouées', $failed, 'integer'),
            $this->kpi('pending', 'En attente', $pending, 'integer'),
            $this->kpi('refunded', 'Remboursées', $refunded, 'integer'),
            $this->kpi('active_subscriptions', 'Abonnements actifs', $activeSubscriptions, 'integer', hint: 'Contrats en cours, tous plans confondus'),
            $this->kpi('investment_payments', 'Paiements d\'investissement exclus', $investmentPayments, 'integer', hint: 'Les fonds d\'escrow ne sont jamais du revenu'),
        ], $this->currencyKpis(
            'revenue',
            'Encaissements réussis',
            $revenueTotals,
            $variations,
            $period->previousLabel,
            'Paiements d\'abonnement réussis sur la période',
        ), $this->currencyKpis(
            'mrr',
            'Revenu récurrent contracté',
            $mrr,
            hint: 'MRR des abonnements actifs, prorata annuel',
        ));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function charts(StatisticsPeriod $period, array $filters): array
    {
        $charts = [
            $this->providerChart($filters),
            $this->statusChart($filters),
            $this->planChart(),
        ];

        foreach ((array) config('statistics.currencies', ['USD', 'CDF']) as $currency) {
            $code = strtoupper((string) $currency);

            $query = (clone $this->baseQuery($filters))
                ->where('status', PaymentStatus::Succeeded->value)
                ->where('currency', $code);

            if (! (clone $query)->exists()) {
                continue;
            }

            $charts[] = $this->lineChart(
                'revenue_'.strtolower($code),
                'area',
                'Encaissements en '.$code,
                'Paiements d\'abonnement réussis par '.($period->granularity === 'month' ? 'mois' : 'jour').'.',
                [
                    'amount' => $this->bucketSums($query, 'created_at', 'amount_minor', $period, divisor: 100),
                ],
                ['amount' => ['label' => $code, 'color' => 'chart-2']],
                $period,
            );
        }

        return $charts;
    }

    /** @param array<string, mixed> $filters */
    public function tableQuery(array $filters): Builder
    {
        return $this->baseQuery($filters)->with(['payer', 'payable']);
    }

    /** @param array<string, mixed> $filters */
    public function paginatedTable(array $filters): LengthAwarePaginator
    {
        return $this->paginate(
            $this->tableQuery($filters),
            $filters,
            self::SORTABLE,
            'created_at',
        )->through(fn (Payment $payment) => $this->mapRow($payment));
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
            ->map(fn (Payment $payment) => $this->mapRow($payment));
    }

    /** @return array<int, array<string, mixed>> */
    public function tableColumns(): array
    {
        return [
            ['key' => 'created_at', 'label' => 'Date', 'sortable' => true, 'type' => 'date'],
            ['key' => 'payer', 'label' => 'Payeur', 'sortable' => false, 'type' => 'text'],
            ['key' => 'payer_email', 'label' => 'E-mail', 'sortable' => false, 'type' => 'text'],
            ['key' => 'plan', 'label' => 'Plan', 'sortable' => false, 'type' => 'text'],
            ['key' => 'provider', 'label' => 'Fournisseur', 'sortable' => false, 'type' => 'text'],
            ['key' => 'amount', 'label' => 'Montant', 'sortable' => true, 'type' => 'currency', 'currency_key' => 'currency'],
            ['key' => 'currency', 'label' => 'Devise', 'sortable' => false, 'type' => 'text'],
            ['key' => 'status', 'label' => 'Statut', 'sortable' => true, 'type' => 'badge'],
        ];
    }

    protected function title(): string
    {
        return 'Analyse des revenus';
    }

    protected function description(): string
    {
        return 'Revenus d\'abonnement enregistrés dans le grand livre des paiements, excluant les fonds d\'escrow.';
    }

    /** @param array<string, mixed> $filters */
    private function baseQuery(array $filters): Builder
    {
        $query = Payment::query()->where('purpose', PaymentPurpose::Subscription->value);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['provider'])) {
            $query->where('provider', $filters['provider']);
        }

        if (! empty($filters['currency'])) {
            $query->where('currency', strtoupper((string) $filters['currency']));
        }

        if (! empty($filters['plan_id'])) {
            $query->where('payable_type', SubscriptionPlan::class)
                ->where('payable_id', $filters['plan_id']);
        }

        if (! empty($filters['search'])) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim((string) $filters['search'])).'%';

            $query->where(function (Builder $search) use ($term) {
                $search->where('provider_reference', 'like', $term)
                    ->orWhereHas('payer', fn (Builder $payer) => $payer->where('name', 'like', $term)->orWhere('email', 'like', $term));
            });
        }

        return $query;
    }

    /** @return array<string, int> minor units keyed by currency */
    private function monthlyRecurringRevenue(): array
    {
        $totals = [];

        $subscriptions = Subscription::query()
            ->with('plan')
            ->whereIn('status', [SubscriptionStatus::Active->value, SubscriptionStatus::Trialing->value])
            ->get();

        foreach ($subscriptions as $subscription) {
            $plan = $subscription->plan;

            if ($plan === null) {
                continue;
            }

            $annual = $subscription->billing_period === 'annual';
            $price = $plan->priceMinorFor($annual ? 'annual' : 'monthly');

            if ($price === null) {
                continue;
            }

            $currency = strtoupper((string) $plan->currency);
            $monthly = $annual ? $price / 12 : $price;
            $totals[$currency] = (int) round(($totals[$currency] ?? 0) + $monthly);
        }

        return $totals;
    }

    /** @param array<string, mixed> $filters */
    private function providerChart(array $filters): array
    {
        $rows = (clone $this->baseQuery($filters))
            ->where('status', PaymentStatus::Succeeded->value)
            ->selectRaw('provider as name, count(*) as total')
            ->groupBy('provider')
            ->get();

        $labels = [];
        $counts = [];

        foreach ($rows as $row) {
            $provider = PaymentProviderName::tryFrom((string) $row->name);
            $labels[(string) $row->name] = $provider?->label() ?? (string) $row->name;
            $counts[(string) $row->name] = (int) $row->total;
        }

        return $this->pieChart(
            'providers',
            'Transactions réussies par fournisseur',
            'Répartition en nombre, sans additionner les devises.',
            $this->pieItems($counts, $labels),
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
            $status = PaymentStatus::tryFrom((string) $row->name);
            $labels[(string) $row->name] = $status?->label() ?? (string) $row->name;
            $counts[(string) $row->name] = (int) $row->total;
        }

        return $this->pieChart(
            'statuses',
            'Répartition par statut',
            'État courant des transactions d\'abonnement.',
            $this->pieItems($counts, $labels),
        );
    }

    private function planChart(): array
    {
        $rows = DB::table('plan_subscriptions')
            ->join('subscription_plans', 'subscription_plans.id', '=', 'plan_subscriptions.subscription_plan_id')
            ->whereIn('plan_subscriptions.status', [SubscriptionStatus::Active->value, SubscriptionStatus::Trialing->value])
            ->selectRaw('subscription_plans.name as name, count(*) as total')
            ->groupBy('subscription_plans.name')
            ->get();

        $counts = [];

        foreach ($rows as $row) {
            $counts[(string) $row->name] = (int) $row->total;
        }

        return $this->pieChart(
            'plans',
            'Abonnements actifs par plan',
            'Contrats en cours au moment du rapport.',
            $this->pieItems($counts),
        );
    }

    /** @return array<string, mixed> */
    private function mapRow(Payment $payment): array
    {
        $status = $payment->status;
        $provider = $payment->provider;
        $plan = $payment->payable instanceof SubscriptionPlan ? $payment->payable->name : '—';

        return [
            'id' => $payment->id,
            'created_at' => $payment->created_at?->toIso8601String(),
            'payer' => $payment->payer?->name ?? '—',
            'payer_email' => $payment->payer?->email ?? '—',
            'plan' => $plan,
            'provider' => $provider instanceof PaymentProviderName ? $provider->label() : (string) $provider,
            'amount' => round(((int) $payment->amount_minor) / 100, 2),
            'currency' => strtoupper((string) $payment->currency),
            'status' => $status instanceof PaymentStatus ? $status->value : (string) $status,
            'status_label' => $status instanceof PaymentStatus ? $status->label() : (string) $status,
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
            $labels['Statut'] = PaymentStatus::tryFrom((string) $filters['status'])?->label() ?? (string) $filters['status'];
        }

        if (! empty($filters['provider'])) {
            $labels['Fournisseur'] = PaymentProviderName::tryFrom((string) $filters['provider'])?->label() ?? (string) $filters['provider'];
        }

        if (! empty($filters['currency'])) {
            $labels['Devise'] = strtoupper((string) $filters['currency']);
        }

        if (! empty($filters['plan_id'])) {
            $labels['Plan'] = SubscriptionPlan::find($filters['plan_id'])?->name ?? (string) $filters['plan_id'];
        }

        if (! empty($filters['search'])) {
            $labels['Recherche'] = (string) $filters['search'];
        }

        return $labels;
    }
}
