<?php

namespace App\Services\Statistics;

use App\Enums\EscrowStatus;
use App\Enums\FundingType;
use App\Enums\InvestmentHorizon;
use App\Enums\InvestorType;
use App\Enums\RiskAppetite;
use App\Models\Escrow;
use App\Models\FinancingOffer;
use App\Models\InvestorProfile;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InvestorsStatisticsService extends StatisticsService
{
    public const KEY = 'investors';

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
            ->whereHas('investorProfile', fn (Builder $query) => $query->where('onboarding_completed', true))
            ->count();

        $withInvestments = (clone $base)->has('investments')->count();

        $countries = DB::table('investor_profiles')
            ->whereIn('user_id', (clone $base)->select('id'))
            ->whereNotNull('country_code')
            ->distinct()
            ->count('country_code');

        $realized = $this->realizedEscrows($base);
        $realizedTotals = $this->totalsByCurrency(clone $realized);
        $previousTotals = $this->totalsByCurrency(
            $this->applyPreviousPeriod(clone $realized, 'funded_at', $period),
        );

        $variations = [];

        foreach ($realizedTotals as $currency => $minor) {
            $variations[$currency] = $this->variation($minor, $previousTotals[$currency] ?? 0);
        }

        $pipeline = FinancingOffer::query()
            ->whereIn('investor_profile_id', InvestorProfile::query()->whereIn('user_id', (clone $base)->select('id'))->select('id'))
            ->count();

        return array_merge([
            $this->kpi('total', 'Investisseurs', $total, 'integer', hint: 'Comptes portant le rôle investisseur'),
            $this->kpi('new', 'Nouveaux investisseurs', $new, 'integer', $this->variation($new, $previousNew), $period->previousLabel),
            $this->kpi('active', 'Actifs', $active, 'integer', $this->variation($active, $previousActive), $period->previousLabel, 'Activité enregistrée sur la période'),
            $this->kpi('onboarding_complete', 'Profils complétés', $onboardingComplete, 'integer', hint: 'Onboarding investisseur terminé'),
            $this->kpi('with_investments', 'Investisseurs actifs', $withInvestments, 'integer', hint: 'Au moins un investissement réalisé'),
            $this->kpi('countries', 'Pays représentés', $countries, 'integer'),
            $this->kpi('pipeline', 'Offres en pipeline', $pipeline, 'integer', hint: 'Offres de financement non réalisées'),
        ], $this->currencyKpis(
            'invested',
            'Capital investi',
            $realizedTotals,
            $variations,
            $period->previousLabel,
            'Escrows financés, hors remboursés et annulés',
        ));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function charts(StatisticsPeriod $period, array $filters): array
    {
        $charts = [
            $this->registrationsChart($period, $filters),
            $this->typeChart($filters),
            $this->riskChart($filters),
            $this->countryChart($filters),
        ];

        foreach (array_merge((array) config('statistics.currencies', ['USD', 'CDF']), []) as $currency) {
            $code = strtoupper((string) $currency);

            $query = $this->realizedEscrows($this->baseQuery($filters))
                ->where('currency', $code);

            if (! (clone $query)->exists()) {
                continue;
            }

            $charts[] = $this->lineChart(
                'invested_'.strtolower($code),
                'area',
                'Capital investi en '.$code,
                'Montants financés par '.($period->granularity === 'month' ? 'mois' : 'jour').', hors escrows remboursés.',
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
            ->with(['investorProfile' => fn ($query) => $query->withCount('financingOffers')])
            ->withCount('investments')
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
            ['key' => 'investor_type', 'label' => 'Type', 'sortable' => false, 'type' => 'text'],
            ['key' => 'country_code', 'label' => 'Pays', 'sortable' => false, 'type' => 'text'],
            ['key' => 'ticket_range', 'label' => 'Ticket déclaré', 'sortable' => false, 'type' => 'text'],
            ['key' => 'investments_count', 'label' => 'Investissements', 'sortable' => false, 'type' => 'number'],
            ['key' => 'offers_count', 'label' => 'Offres', 'sortable' => false, 'type' => 'number'],
            ['key' => 'onboarding', 'label' => 'Profil', 'sortable' => false, 'type' => 'badge'],
            ['key' => 'last_active_at', 'label' => 'Dernière activité', 'sortable' => true, 'type' => 'date'],
            ['key' => 'created_at', 'label' => 'Inscription', 'sortable' => true, 'type' => 'date'],
        ];
    }

    protected function title(): string
    {
        return 'Analyse des investisseurs';
    }

    protected function description(): string
    {
        return 'Base investisseurs, préférences déclarées, capital réalisé et pipeline d\'offres.';
    }

    /** @param array<string, mixed> $filters */
    private function baseQuery(array $filters): Builder
    {
        $query = User::query()->role('investor');

        if (! empty($filters['country_code'])) {
            $query->whereHas('investorProfile', fn (Builder $profile) => $profile->where('country_code', strtoupper((string) $filters['country_code'])));
        }

        if (! empty($filters['investor_type'])) {
            $query->whereHas('investorProfile', fn (Builder $profile) => $profile->where('investor_type', $filters['investor_type']));
        }

        if (! empty($filters['funding_type'])) {
            $query->whereHas('investorProfile', fn (Builder $profile) => $profile->where('funding_type', $filters['funding_type']));
        }

        if (! empty($filters['risk_appetite'])) {
            $query->whereHas('investorProfile', fn (Builder $profile) => $profile->where('risk_appetite', $filters['risk_appetite']));
        }

        if (! empty($filters['investment_horizon'])) {
            $query->whereHas('investorProfile', fn (Builder $profile) => $profile->where('investment_horizon', $filters['investment_horizon']));
        }

        if (! empty($filters['onboarding'])) {
            $query->whereHas('investorProfile', fn (Builder $profile) => $profile->where(
                'onboarding_completed',
                $filters['onboarding'] === 'complete',
            ));
        }

        if (! empty($filters['account_status'])) {
            $this->applyAccountStatus($query, (string) $filters['account_status']);
        }

        $this->applySearch($query, $filters['search'] ?? null, ['name', 'email']);

        return $query;
    }

    /** @param Builder $base filtered user query */
    private function realizedEscrows(Builder $base): Builder
    {
        return Escrow::query()
            ->whereIn('investor_user_id', (clone $base)->select('id'))
            ->whereNotNull('funded_at')
            ->whereNotIn('status', [EscrowStatus::Refunded->value, EscrowStatus::Cancelled->value]);
    }

    /** @param array<string, mixed> $filters */
    private function registrationsChart(StatisticsPeriod $period, array $filters): array
    {
        return $this->lineChart(
            'registrations',
            'line',
            'Évolution des inscriptions',
            'Nouveaux comptes investisseur par '.($period->granularity === 'month' ? 'mois' : 'jour').'.',
            [
                'total' => $this->bucketCounts($this->baseQuery($filters), 'users.created_at', $period),
                'active' => $this->bucketCounts(
                    $this->activeInPeriod($this->baseQuery($filters), $period),
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
    private function typeChart(array $filters): array
    {
        $rows = DB::table('investor_profiles')
            ->whereIn('user_id', (clone $this->baseQuery($filters))->select('id'))
            ->whereNotNull('investor_type')
            ->selectRaw('investor_type as name, count(*) as total')
            ->groupBy('investor_type')
            ->get();

        $labels = [];
        $counts = [];

        foreach ($rows as $row) {
            $type = InvestorType::tryFrom((string) $row->name);
            $labels[(string) $row->name] = $type?->label() ?? (string) $row->name;
            $counts[(string) $row->name] = (int) $row->total;
        }

        return $this->pieChart(
            'investor_types',
            'Répartition par type d\'investisseur',
            'Catégorie déclarée dans le profil investisseur.',
            $this->pieItems($counts, $labels),
        );
    }

    /** @param array<string, mixed> $filters */
    private function riskChart(array $filters): array
    {
        $rows = DB::table('investor_profiles')
            ->whereIn('user_id', (clone $this->baseQuery($filters))->select('id'))
            ->whereNotNull('risk_appetite')
            ->selectRaw('risk_appetite as name, count(*) as total')
            ->groupBy('risk_appetite')
            ->get();

        $labels = [];
        $counts = [];

        foreach ($rows as $row) {
            $risk = RiskAppetite::tryFrom((string) $row->name);
            $labels[(string) $row->name] = $risk?->label() ?? (string) $row->name;
            $counts[(string) $row->name] = (int) $row->total;
        }

        return $this->pieChart(
            'risk_appetite',
            'Appétence au risque',
            'Préférence déclarée par les investisseurs.',
            $this->pieItems($counts, $labels),
        );
    }

    /** @param array<string, mixed> $filters */
    private function countryChart(array $filters): array
    {
        $rows = DB::table('investor_profiles')
            ->whereIn('user_id', (clone $this->baseQuery($filters))->select('id'))
            ->whereNotNull('country_code')
            ->selectRaw('country_code as name, count(*) as total')
            ->groupBy('country_code')
            ->get();

        $counts = [];

        foreach ($rows as $row) {
            $counts[strtoupper((string) $row->name)] = (int) $row->total;
        }

        return $this->pieChart(
            'countries',
            'Répartition géographique',
            'Pays déclarés par les investisseurs.',
            $this->pieItems($counts, limit: 8),
        );
    }

    /** @return array<string, mixed> */
    private function mapRow(User $user): array
    {
        $profile = $user->investorProfile;
        $lastActive = $this->latestActivity($user);
        $type = $profile?->investor_type;
        $typeEnum = $type instanceof InvestorType ? $type : (is_string($type) ? InvestorType::tryFrom($type) : null);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'investor_type' => $typeEnum?->label() ?? '—',
            'country_code' => $profile?->country_code !== null ? strtoupper((string) $profile->country_code) : '—',
            'ticket_range' => $profile?->ticketRangeLabel() ?? '—',
            'investments_count' => (int) ($user->investments_count ?? 0),
            'offers_count' => (int) ($profile?->financing_offers_count ?? 0),
            'onboarding' => $profile?->onboarding_completed ? 'complete' : 'incomplete',
            'onboarding_label' => $profile?->onboarding_completed ? 'Complet' : 'Incomplet',
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

        if (! empty($filters['country_code'])) {
            $labels['Pays'] = strtoupper((string) $filters['country_code']);
        }

        if (! empty($filters['investor_type'])) {
            $labels['Type d\'investisseur'] = InvestorType::tryFrom((string) $filters['investor_type'])?->label() ?? (string) $filters['investor_type'];
        }

        if (! empty($filters['funding_type'])) {
            $labels['Instrument'] = FundingType::tryFrom((string) $filters['funding_type'])?->label() ?? (string) $filters['funding_type'];
        }

        if (! empty($filters['risk_appetite'])) {
            $labels['Appétence au risque'] = RiskAppetite::tryFrom((string) $filters['risk_appetite'])?->label() ?? (string) $filters['risk_appetite'];
        }

        if (! empty($filters['investment_horizon'])) {
            $labels['Horizon'] = InvestmentHorizon::tryFrom((string) $filters['investment_horizon'])?->label() ?? (string) $filters['investment_horizon'];
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
