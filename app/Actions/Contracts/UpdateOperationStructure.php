<?php

namespace App\Actions\Contracts;

use App\Enums\EquityMechanism;
use App\Enums\FinancingCategory;
use App\Enums\FinancingComponentType;
use App\Enums\NegotiationTermStatus;
use App\Enums\WorkspaceStatus;
use App\Models\ContractFinancingComponent;
use App\Models\InvestmentContractWorkspace;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Contracts\CapTableCalculationService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Structure de l'opération (prompt §7) : composantes capital /
 * dette / convertible (jamais fusionnées), valorisations en unités
 * mineures, participations en points de base. Données partielles
 * restent permises (montant absent, bps non fixés...) : les
 * contradictions sont signalées par les contrôles financiers
 * déterministes, jamais corrigées en silence.
 */
class UpdateOperationStructure
{
    /**
     * Clés admises dans les modalités propres d'une composante.
     *
     * @var list<string>
     */
    private const TERM_KEYS = [
        'rate_bps', 'schedule', 'duration_months', 'deferral_months',
        'guarantees', 'unsecured', 'converts_to_equity',
        'conversion_discount_bps', 'nominal_value_minor',
    ];

    public function __construct(
        private AuditLogger $audit,
        private CapTableCalculationService $capTable,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function handle(User $user, InvestmentContractWorkspace $workspace, array $input = []): InvestmentContractWorkspace
    {
        if (! $workspace->isParticipant($user)) {
            throw ValidationException::withMessages([
                'workspace' => 'Seuls les participants de l\'opération peuvent en modifier la structure.',
            ]);
        }

        if ($workspace->isClosed() || $workspace->status === WorkspaceStatus::Cancelled) {
            throw ValidationException::withMessages([
                'workspace' => 'L\'opération est clôturée ou annulée : sa structure est figée.',
            ]);
        }

        $data = $this->validated($input);
        $postMoneyProvided = array_key_exists('post_money_minor', $input);

        // Composantes : remplacement INTEGRAL de la liste fournie —
        // la structure est vue comme un tout cohérent affiché et
        // contrôlé en bloc, jamais patchée morceau par morceau.
        $components = $this->verifiedComponents($data['components'] ?? null);
        if ($components !== null) {
            $this->replaceComponents($workspace, $components);
        }

        foreach (['pre_money_minor', 'post_money_minor', 'total_investment_minor', 'founder_share_bps', 'investor_share_bps'] as $field) {
            if (array_key_exists($field, $data)) {
                $workspace->{$field} = $data[$field];
            }
        }

        // Post-money manquante : dérivée de manière déterministe
        // (pré-money + CAPITAL — la dette n'y entre jamais, §16
        // r.5). Une valeur explicitement soumise n'est jamais
        // écrasée : la contradiction reste signalée par les
        // contrôles financiers.
        if (! $postMoneyProvided && $workspace->pre_money_minor !== null) {
            $capitalAmount = (int) $workspace->financingComponents()
                ->get()
                ->filter(fn (ContractFinancingComponent $component) => $component->type === FinancingComponentType::Capital)
                ->sum('amount_minor');

            if ($capitalAmount > 0) {
                $workspace->post_money_minor = $workspace->pre_money_minor + $capitalAmount;
            }
        }

        // Catégorie déduite des composantes réelles (§7) : jamais
        // saisie, jamais supposée.
        $workspace->financing_category = $this->derivedCategory($workspace);

        // Projection déterministe de la table de capitalisation :
        // seuls les instruments donnant accès au capital y
        // figurent — la dette (§16, r.5) n'y entre jamais.
        $projection = $this->capTable->calculate($workspace);
        $workspace->post_operation_cap_table = $projection?->toSnapshot();

        if ($workspace->status === WorkspaceStatus::Draft) {
            $workspace->status = WorkspaceStatus::Setup;
        }

        $workspace->last_activity_at = now();
        $workspace->save();

        $this->audit->log($user, 'workspace.structure_updated', $workspace, [
            'workspace_id' => $workspace->id,
            'financing_category' => $workspace->financing_category?->value,
            'cap_table_computed' => $projection !== null,
        ]);

        return $workspace->fresh();
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function validated(array $input): array
    {
        $validated = Validator::make($input, [
            'pre_money_minor' => ['sometimes', 'integer', 'min:0'],
            'post_money_minor' => ['sometimes', 'integer', 'min:0'],
            'total_investment_minor' => ['sometimes', 'integer', 'min:0'],
            'founder_share_bps' => ['sometimes', 'integer', 'min:0', 'max:10000'],
            'investor_share_bps' => ['sometimes', 'integer', 'min:0', 'max:10000'],
            'components' => ['sometimes', 'array', 'max:20'],
            'components.*.type' => ['required', Rule::enum(FinancingComponentType::class)],
            'components.*.equity_mechanism' => ['sometimes', Rule::enum(EquityMechanism::class)],
            'components.*.amount_minor' => ['required', 'integer', 'min:0'],
            'components.*.currency' => ['sometimes', 'string', 'size:3'],
            'components.*.is_primary' => ['sometimes', 'boolean'],
            'components.*.terms' => ['sometimes', 'array'],
        ])->validate();

        foreach ($this->verifiedComponents($validated['components'] ?? null) ?? [] as $component) {
            // Mécanisme juridique : réservé au capital (§3) —
            // qu'une composante dette porte un "mécanisme" serait
            // une confusion de nature, refusée plutôt que
            // silencieusement ignorée.
            if (($component['equity_mechanism'] ?? null) !== null
                && $component['type'] !== FinancingComponentType::Capital->value) {
                throw ValidationException::withMessages([
                    'components' => 'Le mécanisme juridique (augmentation de capital, cession de titres) ne s\'applique qu\'à une composante en capital.',
                ]);
            }

            if (array_key_exists('terms', $component)) {
                $this->assertTermShape($component['terms']);
                $this->validateTerms($component['terms'], $component['type']);
            }
        }

        $primaryCount = count(array_filter(
            $this->verifiedComponents($validated['components'] ?? null) ?? [],
            fn (array $component): bool => (bool) ($component['is_primary'] ?? false),
        ));

        if ($primaryCount > 1) {
            throw ValidationException::withMessages([
                'components' => 'Une seule composante peut porter le rôle d\'instrument principal.',
            ]);
        }

        return $validated;
    }

    /**
     * La règle « components.* » garantie par le Validator et la
     * forme exigée par la suite des traitements : le même contrat
     * pour le moteur et pour l'analyse statique, sans transtypage
     * masqué. `null` = la clé est absente.
     *
     * @return list<array<string, mixed>>|null
     */
    private function verifiedComponents(mixed $components): ?array
    {
        if ($components === null) {
            return null;
        }

        if (! is_array($components)) {
            throw ValidationException::withMessages([
                'components' => 'Structure de composantes invalide.',
            ]);
        }

        $list = [];

        foreach ($components as $component) {
            if (! is_array($component)) {
                throw ValidationException::withMessages([
                    'components' => 'Structure de composante invalide.',
                ]);
            }

            if (! is_string($component['type'] ?? null) || ! is_int($component['amount_minor'] ?? null)) {
                throw ValidationException::withMessages([
                    'components' => 'Composante incomplète : instrument et montant requis.',
                ]);
            }

            $list[] = $component;
        }

        return $list;
    }

    private function assertTermShape(mixed $terms): void
    {
        if (! is_array($terms)) {
            throw ValidationException::withMessages([
                'components' => 'Modalités de composante invalides.',
            ]);
        }
    }

    /**
     * Modalités propres : clé par clé, uniquement les données
     * structurées prévues — aucune structure libre injectée en base.
     *
     * @param  array<string, mixed>  $terms
     */
    private function validateTerms(array $terms, string $type): void
    {
        $disallowed = array_diff(array_keys($terms), self::TERM_KEYS);

        if ($disallowed !== []) {
            throw ValidationException::withMessages([
                'components' => 'Modalités de composante non prévues : '.implode(', ', $disallowed).'.',
            ]);
        }

        Validator::make($terms, [
            'rate_bps' => ['sometimes', 'integer', 'min:0', 'max:10000'],
            'duration_months' => ['sometimes', 'integer', 'min:1', 'max:1200'],
            'deferral_months' => ['sometimes', 'integer', 'min:0', 'max:1200', 'lt:duration_months'],
            'unsecured' => ['sometimes', 'boolean'],
            'converts_to_equity' => ['sometimes', 'boolean'],
            'conversion_discount_bps' => ['sometimes', 'integer', 'min:0', 'max:5000'],
            'nominal_value_minor' => ['sometimes', 'integer', 'min:0'],
            'schedule' => ['sometimes', 'array', 'max:60'],
            'schedule.*.amount_minor' => ['required', 'integer', 'min:0'],
            'guarantees' => ['sometimes', 'array', 'max:30'],
            'guarantees.*.label' => ['required', 'string', 'max:200'],
            'guarantees.*.value_minor' => ['sometimes', 'integer', 'min:0'],
        ], [
            'deferral_months.lt' => 'Le différé doit être strictement inférieur à la durée du prêt.',
        ])->validate();
    }

    /**
     * @param  list<array<string, mixed>>  $components
     */
    private function replaceComponents(InvestmentContractWorkspace $workspace, array $components): void
    {
        ContractFinancingComponent::query()
            ->where('investment_contract_workspace_id', $workspace->id)
            ->delete();

        foreach (array_values($components) as $index => $component) {
            $terms = $component['terms'] ?? null;

            if ($terms !== null) {
                $terms = collect(self::TERM_KEYS)
                    ->filter(fn (string $key) => isset($terms[$key]))
                    ->mapWithKeys(fn (string $key) => [$key => $terms[$key]])
                    ->all();
            }

            ContractFinancingComponent::forceCreate([
                'investment_contract_workspace_id' => $workspace->id,
                'type' => $component['type'],
                'equity_mechanism' => $component['equity_mechanism'] ?? null,
                'amount_minor' => $component['amount_minor'],
                'currency' => isset($component['currency'])
                    ? strtoupper(Str::squish((string) $component['currency']))
                    : $workspace->currency,
                'is_primary' => (bool) ($component['is_primary'] ?? false),
                'terms' => filled($terms) ? $terms : null,
                'status' => NegotiationTermStatus::UnderDiscussion->value,
                'sort_order' => $index,
            ]);
        }
    }

    private function derivedCategory(InvestmentContractWorkspace $workspace): ?FinancingCategory
    {
        $types = $workspace->financingComponents()
            ->get()
            ->map(fn (ContractFinancingComponent $component) => $component->type)
            ->unique()
            ->values();

        if ($types->isEmpty()) {
            return null;
        }

        if ($types->count() === 1) {
            return match ($types->first()) {
                FinancingComponentType::Capital => FinancingCategory::Equity,
                FinancingComponentType::Debt => FinancingCategory::Debt,
                FinancingComponentType::Convertible => FinancingCategory::Convertible,
            };
        }

        return FinancingCategory::Mixed;
    }
}
