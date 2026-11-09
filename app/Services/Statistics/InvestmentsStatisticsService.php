<?php

namespace App\Services\Statistics;

use App\Enums\EscrowStatus;
use App\Models\Escrow;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class InvestmentsStatisticsService extends StatisticsService
{
    public const KEY = 'investments';

    /** @var array<int, string> */
    private const SORTABLE = ['amount_minor', 'funded_at', 'released_at', 'created_at'];

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function kpis(StatisticsPeriod $period, array $filters): array
    {
        $base = $this->baseQuery($filters);

        $total = (clone $base)->count();
        $funded = (clone $base)->whereNotNull('funded_at')->count();
        $released = (clone $base)->where('status', EscrowStatus::Released->value)->count();
        $pending = (clone $base)->where('status', EscrowStatus::Pending->value)->count();
        $refunded = (clone $base)->where('status', EscrowStatus::Refunded->value)->count();
        $disputed = (clone $base)->where('status', EscrowStatus::Disputed->value)->count();

        $realized = $this->realizedEscrows($base);
        $realizedTotals = $this->totalsByCurrency(clone $realized);
        $previousTotals = $this->totalsByCurrency(
            $this->applyPreviousPeriod(clone $realized, 'funded_at', $period),
        );

        $variations = [];

        foreach ($realizedTotals as $currency => $minor) {
            $variations[$currency] = $this->variation($minor, $previousTotals[$currency] ?? 0);
        }

        $releasedTotals = $this->totalsByCurrency(
            (clone $base)->where('status', EscrowStatus::Released->value),
        );

        $heldTotals = $this->totalsByCurrency(
            (clone $base)->whereIn('status', [
                EscrowStatus::Funded->value,
                EscrowStatus::Held->value,
                EscrowStatus::AwaitingRelease->value,
                EscrowStatus::RefundRequested->value,
                EscrowStatus::Disputed->value,
            ]),
        );

        return array_merge([
            $this->kpi('total', 'Escrows', $total, 'integer', hint: 'Investissements matérialisés par séquestre'),
            $this->kpi('funded', 'Financés', $funded, 'integer', hint: 'Escrows avec un financement capturé'),
            $this->kpi('released', 'Libérés', $released, 'integer'),
            $this->kpi('pending', 'En attente de financement', $pending, 'integer'),
            $this->kpi('refunded', 'Remboursés', $refunded, 'integer'),
            $this->kpi('disputed', 'En litige', $disputed, 'integer'),
            $this->kpi('release_rate', 'Taux de libération', $this->rate($released, $funded), 'percent', hint: 'Escrows libérés / escrows financés'),
        ], $this->currencyKpis(
            'realized',
            'Capital réalisé',
            $realizedTotals,
            $variations,
            $period->previousLabel,
            'Escrows financés sur la période, hors remboursés et annulés',
        ), $this->currencyKpis(
            'held',
            'Fonds sous séquestre',
            $heldTotals,
            hint: 'Fonds reçus mais non encore libérés',
        ), $this->currencyKpis(
            'released_amount',
            'Fonds libérés',
            $releasedTotals,
            hint: 'Montants effectivement libérés aux entrepreneurs',
        ));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function charts(StatisticsPeriod $period, array $filters): array
    {
        $charts = [
            $this->createdChart($period, $filters),
            $this->statusChart($filters),
            $this->currencyChart($filters),
        ];

        foreach ((array) config('statistics.currencies', ['USD', 'CDF']) as $currency) {
            $code = strtoupper((string) $currency);

            $query = $this->realizedEscrows($this->baseQuery($filters))->where('currency', $code);

            if (! (clone $query)->exists()) {
                continue;
            }

            $charts[] = $this->lineChart(
                'funded_'.strtolower($code),
                'area',
                'Capital réalisé en '.$code,
                'Montants financés par '.($period->granularity === 'month' ? 'mois' : 'jour').', hors remboursés.',
                [
                    'amount' => $this->bucketSums($query, 'funded_at', 'amount_minor', $period, divisor: 100),
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
        return $this->baseQuery($filters)
            ->with(['dealRoom.project', 'investor', 'entrepreneur']);
    }

    /** @param array<string, mixed> $filters */
    public function paginatedTable(array $filters): LengthAwarePaginator
    {
        return $this->paginate(
            $this->tableQuery($filters),
            $filters,
            self::SORTABLE,
            'created_at',
        )->through(fn (Escrow $escrow) => $this->mapRow($escrow));
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
            ->map(fn (Escrow $escrow) => $this->mapRow($escrow));
    }

    /** @return array<int, array<string, mixed>> */
    public function tableColumns(): array
    {
        return [
            ['key' => 'project', 'label' => 'Projet', 'sortable' => false, 'type' => 'text'],
            ['key' => 'investor', 'label' => 'Investisseur', 'sortable' => false, 'type' => 'text'],
            ['key' => 'entrepreneur', 'label' => 'Entrepreneur', 'sortable' => false, 'type' => 'text'],
            ['key' => 'amount', 'label' => 'Montant', 'sortable' => true, 'type' => 'currency', 'currency_key' => 'currency'],
            ['key' => 'status', 'label' => 'Statut', 'sortable' => false, 'type' => 'badge'],
            ['key' => 'funded_at', 'label' => 'Financé le', 'sortable' => true, 'type' => 'date'],
            ['key' => 'released_at', 'label' => 'Libéré le', 'sortable' => true, 'type' => 'date'],
            ['key' => 'created_at', 'label' => 'Créé le', 'sortable' => true, 'type' => 'date'],
        ];
    }

    protected function title(): string
    {
        return 'Analyse des investissements';
    }

    protected function description(): string
    {
        return 'Investissements réalisés via escrow : capital financé, fonds sous séquestre et libérations.';
    }

    /** @param array<string, mixed> $filters */
    private function baseQuery(array $filters): Builder
    {
        $query = Escrow::query();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['currency'])) {
            $query->where('currency', strtoupper((string) $filters['currency']));
        }

        if (! empty($filters['search'])) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim((string) $filters['search'])).'%';

            $query->where(function (Builder $search) use ($term) {
                $search->whereHas('investor', fn (Builder $user) => $user->where('name', 'like', $term)->orWhere('email', 'like', $term))
                    ->orWhereHas('entrepreneur', fn (Builder $user) => $user->where('name', 'like', $term)->orWhere('email', 'like', $term))
                    ->orWhereHas('dealRoom.project', fn (Builder $project) => $project->where('title', 'like', $term));
            });
        }

        return $query;
    }

    /** @param Builder $base filtered escrow query */
    private function realizedEscrows(Builder $base): Builder
    {
        return (clone $base)
            ->whereNotNull('funded_at')
            ->whereNotIn('status', [EscrowStatus::Refunded->value, EscrowStatus::Cancelled->value]);
    }

    /** @param array<string, mixed> $filters */
    private function createdChart(StatisticsPeriod $period, array $filters): array
    {
        return $this->lineChart(
            'created',
            'line',
            'Évolution des escrows',
            'Escrows créés par '.($period->granularity === 'month' ? 'mois' : 'jour').'.',
            [
                'created' => $this->bucketCounts($this->baseQuery($filters), 'escrows.created_at', $period),
                'funded' => $this->bucketCounts(
                    $this->baseQuery($filters)->whereNotNull('escrows.funded_at'),
                    'escrows.funded_at',
                    $period,
                ),
            ],
            [
                'created' => ['label' => 'Créés', 'color' => 'chart-1'],
                'funded' => ['label' => 'Financés', 'color' => 'chart-2'],
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
            $status = EscrowStatus::tryFrom((string) $row->name);
            $labels[(string) $row->name] = $status?->label() ?? (string) $row->name;
            $counts[(string) $row->name] = (int) $row->total;
        }

        return $this->pieChart(
            'statuses',
            'Répartition par statut',
            'État courant des escrows filtrés.',
            $this->pieItems($counts, $labels),
        );
    }

    /** @param array<string, mixed> $filters */
    private function currencyChart(array $filters): array
    {
        $rows = (clone $this->baseQuery($filters))
            ->selectRaw('currency as name, count(*) as total')
            ->groupBy('currency')
            ->get();

        $counts = [];

        foreach ($rows as $row) {
            $counts[strtoupper((string) $row->name)] = (int) $row->total;
        }

        return $this->pieChart(
            'currencies',
            'Répartition par devise',
            'Montants jamais additionnés entre devises.',
            $this->pieItems($counts),
        );
    }

    /** @return array<string, mixed> */
    private function mapRow(Escrow $escrow): array
    {
        $status = $escrow->status;

        return [
            'id' => $escrow->id,
            'project' => $escrow->dealRoom?->project?->title ?? '—',
            'investor' => $escrow->investor?->name ?? '—',
            'entrepreneur' => $escrow->entrepreneur?->name ?? '—',
            'amount' => round(((int) $escrow->amount_minor) / 100, 2),
            'currency' => strtoupper((string) $escrow->currency),
            'status' => $status instanceof EscrowStatus ? $status->value : (string) $status,
            'status_label' => $status instanceof EscrowStatus ? $status->label() : (string) $status,
            'funded_at' => $escrow->funded_at?->toIso8601String(),
            'released_at' => $escrow->released_at?->toIso8601String(),
            'created_at' => $escrow->created_at?->toIso8601String(),
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
            $labels['Statut'] = EscrowStatus::tryFrom((string) $filters['status'])?->label() ?? (string) $filters['status'];
        }

        if (! empty($filters['currency'])) {
            $labels['Devise'] = strtoupper((string) $filters['currency']);
        }

        if (! empty($filters['search'])) {
            $labels['Recherche'] = (string) $filters['search'];
        }

        return $labels;
    }
}
