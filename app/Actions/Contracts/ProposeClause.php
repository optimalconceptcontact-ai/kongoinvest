<?php

namespace App\Actions\Contracts;

use App\Enums\ClauseCategory;
use App\Enums\ClauseOrigin;
use App\Enums\ClauseStatus;
use App\Enums\FinancingComponentType;
use App\Enums\LegalForm;
use App\Enums\WorkspaceStatus;
use App\Models\CompanyLegalRecord;
use App\Models\ContractClauseLibrary;
use App\Models\ContractClauseSelection;
use App\Models\InvestmentContractWorkspace;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Proposition de clause par une PARTIE (prompt §5, §8) : depuis la
 * bibliothèque structurée (texte copié, jamais réécrit) ou
 * personnalisée (title + body requis). L'origine est déduite du
 * côté du proposant — jamais déclarée par l'appelant.
 */
class ProposeClause
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function handle(User $user, InvestmentContractWorkspace $workspace, array $input = []): ContractClauseSelection
    {
        if (! $workspace->isParticipant($user)) {
            throw ValidationException::withMessages([
                'workspace' => 'Seuls les participants de l\'opération peuvent proposer une clause.',
            ]);
        }

        if ($workspace->isClosed() || $workspace->status === WorkspaceStatus::Cancelled) {
            throw ValidationException::withMessages([
                'workspace' => 'L\'opération est clôturée ou annulée : plus de proposition possible.',
            ]);
        }

        $data = Validator::make($input, [
            'source' => ['required', 'string', 'in:library,custom'],
            'library_id' => ['required_if:source,library', 'integer'],
            'variant' => ['sometimes', 'string', 'max:80'],
            'title' => ['required_if:source,custom', 'string', 'min:3', 'max:200'],
            'body' => ['required_if:source,custom', 'string', 'min:10', 'max:20000'],
            'rationale' => ['sometimes', 'string', 'max:2000'],
            'instrument_scope' => ['sometimes', Rule::enum(FinancingComponentType::class)],
        ])->validate();

        $side = $workspace->isFounder($user) ? 'founder' : 'investor';
        $origin = $side === 'founder' ? ClauseOrigin::Founder : ClauseOrigin::Investor;
        $status = $side === 'founder' ? ClauseStatus::ProposedByFounder : ClauseStatus::ProposedByInvestor;

        $attributes = $data['source'] === 'custom'
            ? $this->customAttributes($data, $origin, $status)
            : $this->libraryAttributes($user, $workspace, $data, $origin, $status);

        $selection = ContractClauseSelection::forceCreate([
            'investment_contract_workspace_id' => $workspace->id,
            ...$attributes,
            'is_custom' => $data['source'] === 'custom',
            'proposed_by_user_id' => $user->id,
            'sort_order' => $this->nextSortOrder($workspace),
        ]);

        $workspace->forceFill(['last_activity_at' => now()])->save();

        $this->audit->log($user, 'workspace.clause_proposed', $selection, [
            'workspace_id' => $workspace->id,
            'source' => $data['source'],
            'side' => $side,
        ]);

        return $selection->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function customAttributes(array $data, ClauseOrigin $origin, ClauseStatus $status): array
    {
        return [
            'title' => Str::squish($data['title']),
            'body' => (string) $data['body'],
            'category' => ClauseCategory::General->value,
            'origin' => $origin->value,
            'status' => $status->value,
            'instrument_scope' => $data['instrument_scope'] ?? null,
            'variant' => null,
            'rationale' => $data['rationale'] ?? null,
        ];
    }

    /**
     * Clause de la bibliothèque : éligibilité vérifiée EXACTEMENT
     * comme pour l'IA (instrument de l'opération + forme juridique
     * courante) — une clause inapplicable est refusée, jamais créée
     * puis ignorée, et le texte reste celui de la bibliothèque.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function libraryAttributes(
        User $user,
        InvestmentContractWorkspace $workspace,
        array $data,
        ClauseOrigin $origin,
        ClauseStatus $status,
    ): array {
        $clause = ContractClauseLibrary::query()->active()->whereKey($data['library_id'])->first();

        if ($clause === null) {
            throw ValidationException::withMessages([
                'library_id' => 'Clause de la bibliothèque introuvable ou inactive — jamais de proposition inventée.',
            ]);
        }

        $already = ContractClauseSelection::withTrashed()
            ->where('investment_contract_workspace_id', $workspace->id)
            ->where('contract_clause_library_id', $clause->id)
            ->exists();

        if ($already) {
            throw ValidationException::withMessages([
                'library_id' => 'Cette clause fait déjà partie de l\'opération.',
            ]);
        }

        $form = $this->currentLegalForm($workspace);

        if ($form !== null && ! $clause->allowsLegalForm($form)) {
            throw ValidationException::withMessages([
                'library_id' => "Clause non applicable à la forme juridique {$form->label()}.",
            ]);
        }

        [$eligibleInstrument, $instrumentsRestricted] = $this->instrumentEligibility($workspace, $clause);

        if ($instrumentsRestricted && $eligibleInstrument === null) {
            throw ValidationException::withMessages([
                'library_id' => 'Clause restreinte à des instruments absents de l\'opération.',
            ]);
        }

        $variantKey = $data['variant'] ?? null;
        $body = $clause->body;
        $variant = $variantKey !== null ? $clause->variant($variantKey) : null;

        if ($variant !== null && filled($variant['body'] ?? null)) {
            $body = $variant['body'];
        } elseif ($variantKey !== null && $variant === null) {
            // Variante inconnue : refus explicite, jamais un texte de
            // base présenté comme un texte de variante.
            throw ValidationException::withMessages([
                'variant' => "La variante « {$variantKey} » n'existe pas pour cette clause.",
            ]);
        }

        return [
            'contract_clause_library_id' => $clause->id,
            'library_version' => $clause->current_version,
            'title' => $clause->title,
            'body' => $body,
            'category' => $clause->category->value,
            'origin' => $origin->value,
            'status' => $status->value,
            'instrument_scope' => $instrumentsRestricted ? $eligibleInstrument->value : null,
            'variant' => $variantKey,
            'rationale' => $data['rationale'] ?? null,
        ];
    }

    /**
     * @return array{0: ?FinancingComponentType, 1: bool}
     */
    private function instrumentEligibility(InvestmentContractWorkspace $workspace, ContractClauseLibrary $clause): array
    {
        $restricted = filled($clause->instruments);

        foreach ($workspace->financingComponents as $component) {
            $type = $component->type;

            if ($type instanceof FinancingComponentType && $clause->allowsInstrument($type)) {
                return [$type, $restricted];
            }
        }

        return [null, $restricted];
    }

    private function currentLegalForm(InvestmentContractWorkspace $workspace): ?LegalForm
    {
        if ($workspace->company_id === null) {
            return null;
        }

        $record = CompanyLegalRecord::query()
            ->where('company_id', $workspace->company_id)
            ->where('is_current', true)
            ->first();

        return $record?->form();
    }

    private function nextSortOrder(InvestmentContractWorkspace $workspace): int
    {
        return (int) (ContractClauseSelection::query()
            ->where('investment_contract_workspace_id', $workspace->id)
            ->max('sort_order')) + 1;
    }
}
