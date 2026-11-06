<?php

namespace App\Data;

use App\Enums\DocumentConfidentiality;
use App\Models\ContractClauseLibrary;
use App\Models\InvestmentContractWorkspace;
use App\Models\User;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

/**
 * Payload « espace contractuel » pour la page
 * Contracts/Workspace. Tous les états déjà calculés côté serveur
 * sont figés ici (statuts, consentements, instantanés IA,
 * documents, conditions, signatures, contrôles financiers) : la
 * page React n'interprète rien, elle affiche.
 */
class ContractWorkspaceData extends Data
{
    /**
     * @param  array<int, array<string, mixed>>  $financingComponents
     * @param  array<int, array<string, mixed>>  $terms
     * @param  array<int, array<string, mixed>>  $clauses
     * @param  array<int, array<string, mixed>>  $snapshots
     * @param  array<string, mixed>|null  $postOperationCapTable
     * @param  array<int, array<string, mixed>>  $documents
     * @param  array<int, array<string, mixed>>  $conditions
     * @param  array<int, array<string, mixed>>  $signatures
     * @param  array<int, array<string, mixed>>  $financialChecks
     * @param  array<int, array<string, mixed>>  $legalChecks
     * @param  array<int, array<string, mixed>>  $clauseLibrary
     */
    public function __construct(
        public int $id,
        public int $dealRoomId,
        public string $projectTitle,
        public string $projectSlug,
        public string $entrepreneurName,
        public string $investorName,
        public string $status,
        public string $currency,
        public ?string $financingCategory,
        public ?string $legalFormLabel,
        public string $viewerRole,
        public ?string $entrepreneurConsentAt,
        public ?string $investorConsentAt,
        public bool $bothConsented,
        public ?int $preMoneyMinor,
        public ?int $postMoneyMinor,
        public ?int $totalInvestmentMinor,
        public ?int $founderShareBps,
        public ?int $investorShareBps,
        public array $financingComponents,
        public array $terms,
        public array $clauses,
        public array $snapshots,
        public ?array $postOperationCapTable,
        public array $documents,
        public array $conditions,
        public array $signatures,
        public array $financialChecks,
        public bool $hasBlockingFinancialCheck,
        public array $legalChecks,
        public bool $hasBlockingLegalCheck,
        public array $clauseLibrary,
        public ?string $legalReviewedAt,
        public ?string $lastActivityAt,
    ) {}

    public static function fromModel(InvestmentContractWorkspace $workspace, User $viewer): self
    {
        $workspace->loadMissing([
            'dealRoom.project', 'dealRoom.entrepreneur', 'dealRoom.investor',
            'financingComponents', 'terms', 'clauseSelections', 'snapshots',
            'company.legalRecords',
            'documents', 'conditions', 'signatures', 'financialChecks', 'legalChecks',
        ]);

        // Le libellé de présentation (SARLU/SASU...) est un accesseur
        // déterministe basé sur la fiche juridique courante ; une
        // entreprise non rattachée reste affichable, avec un libellé
        // null — jamais une forme inventée.
        $record = $workspace->company?->legalRecords->first(fn ($r) => $r->is_current);

        // Contrôles : la DERNIÈRE passe seulement — une relance
        // crée un batch nouveau sans effacer l'historique, la page
        // affiche le présent, jamais un filet d'époques mêlées.
        $checks = self::latestBatchChecks($workspace->financialChecks);
        $legalChecks = self::latestBatchChecks($workspace->legalChecks);

        return new self(
            id: $workspace->id,
            dealRoomId: $workspace->deal_room_id,
            projectTitle: $workspace->dealRoom->project->title,
            projectSlug: $workspace->dealRoom->project->slug,
            entrepreneurName: $workspace->dealRoom->entrepreneur->name,
            investorName: $workspace->dealRoom->investor->name,
            status: $workspace->status->value,
            currency: $workspace->currency,
            financingCategory: $workspace->financing_category?->value,
            legalFormLabel: $record?->presentationLabel(),
            viewerRole: $workspace->isFounder($viewer)
                ? 'entrepreneur'
                : ($workspace->isInvestor($viewer) ? 'investor' : 'observer'),
            entrepreneurConsentAt: $workspace->entrepreneur_analysis_consent_at?->toIso8601String(),
            investorConsentAt: $workspace->investor_analysis_consent_at?->toIso8601String(),
            bothConsented: $workspace->conversationAnalysisConsented(),
            preMoneyMinor: $workspace->pre_money_minor,
            postMoneyMinor: $workspace->post_money_minor,
            totalInvestmentMinor: $workspace->total_investment_minor,
            founderShareBps: $workspace->founder_share_bps,
            investorShareBps: $workspace->investor_share_bps,
            financingComponents: $workspace->financingComponents->map(fn ($component) => [
                'id' => $component->id,
                'type' => $component->type->value,
                'amountMinor' => $component->amount_minor,
                'currency' => $component->currency,
                'isPrimary' => (bool) $component->is_primary,
            ])->all(),
            terms: $workspace->terms->map(fn ($term) => [
                'id' => $term->id,
                'subject' => $term->subject,
                'label' => $term->label,
                'category' => $term->category->value,
                'kind' => $term->kind->value,
                'instrument' => $term->instrument?->value,
                'value' => $term->value,
                'displayValue' => $term->display_value,
                'status' => $term->status->value,
                'conflictWithTermId' => $term->conflict_with_term_id,
                'sourceContext' => $term->source_context,
                'notes' => $term->notes,
            ])->all(),
            clauses: $workspace->clauseSelections->map(fn ($clause) => [
                'id' => $clause->id,
                'title' => $clause->title,
                'category' => $clause->category->value,
                'origin' => $clause->origin->value,
                'status' => $clause->status->value,
                'instrumentScope' => $clause->instrument_scope?->value,
                'variant' => $clause->variant,
                'body' => $clause->body,
                'rationale' => $clause->rationale,
                'founderDecision' => $clause->founder_decision?->value,
                'investorDecision' => $clause->investor_decision?->value,
            ])->all(),
            snapshots: $workspace->snapshots->map(fn ($snapshot) => [
                'id' => $snapshot->id,
                'version' => $snapshot->version,
                'status' => $snapshot->status->value,
                'tokensUsed' => $snapshot->tokens_used,
                'messageCount' => $snapshot->coverage['message_count'] ?? null,
                'synthesis' => $snapshot->synthesis,
                'completedAt' => $snapshot->completed_at?->toIso8601String(),
                'failureReason' => $snapshot->failure_reason,
            ])->all(),
            // Instantané de table de capitalisation APRÈS opération
            // (moteur déterministe, prompt §16 règle 5) : la dette n'y
            // figure jamais. CamelCase, même contrat que le reste du
            // payload.
            postOperationCapTable: self::capTableSnapshot($workspace->post_operation_cap_table, $workspace->currency),
            // Confidentialité décentralisée appliquée ICI, à l'émission :
            // un document « fondateur seul » n'appartient pas à
            // l'investisseur, et réciproquement — un observateur ne voit
            // que le partagé. La page n'interprète rien, elle affiche
            // ce qui lui a été servi.
            documents: $workspace->documents
                ->filter(fn ($document) => match ($document->confidentiality) {
                    DocumentConfidentiality::FounderOnly => $workspace->isFounder($viewer),
                    DocumentConfidentiality::InvestorOnly => $workspace->isInvestor($viewer),
                    DocumentConfidentiality::Shared => true,
                })
                ->values()
                ->map(fn ($document) => [
                    'id' => $document->id,
                    'type' => $document->type->value,
                    'title' => $document->title,
                    'status' => $document->status->value,
                    'confidentiality' => $document->confidentiality->value,
                    'currentVersion' => $document->current_version,
                    'founderApprovedAt' => $document->founder_approved_at?->toIso8601String(),
                    'investorApprovedAt' => $document->investor_approved_at?->toIso8601String(),
                    'legalValidatedAt' => $document->legal_validated_at?->toIso8601String(),
                    'signedDepositedAt' => $document->signed_deposited_at?->toIso8601String(),
                ])->all(),
            conditions: $workspace->conditions->map(fn ($condition) => [
                'id' => $condition->id,
                'type' => $condition->type->value,
                'responsibleParty' => $condition->responsible_party->value,
                'description' => $condition->description,
                'dueDate' => $condition->due_date?->toDateString(),
                'status' => $condition->status->value,
                'documentId' => $condition->contract_document_id,
                'fulfilledAt' => $condition->fulfilled_at?->toIso8601String(),
            ])->all(),
            signatures: $workspace->signatures->map(fn ($signature) => [
                'id' => $signature->id,
                'documentId' => $signature->contract_document_id,
                'signerRole' => $signature->signer_role->value,
                'method' => $signature->method->value,
                'status' => $signature->status->value,
                'signedAt' => $signature->signed_at?->toIso8601String(),
                'hasEvidence' => $signature->signature_evidence !== null && $signature->signature_evidence !== [],
            ])->all(),
            financialChecks: $checks->map(fn ($check) => [
                'id' => $check->id,
                'batch' => $check->batch,
                'type' => $check->type->value,
                'severity' => $check->severity->value,
                'status' => $check->status->value,
                'title' => $check->title,
                'message' => $check->message,
                'resolvedAt' => $check->resolved_at?->toIso8601String(),
            ])->all(),
            // Une anomalie critique NON résolue fige la clôture
            // (prompt §10) : précalculée ici, jamais déduite en React.
            hasBlockingFinancialCheck: $checks->contains(fn ($check) => $check->blocksClosing()),
            legalChecks: $legalChecks->map(fn ($check) => [
                'id' => $check->id,
                'batch' => $check->batch,
                'type' => $check->type->value,
                'severity' => $check->severity->value,
                'status' => $check->status->value,
                'title' => $check->title,
                'message' => $check->message,
                'resolvedAt' => $check->resolved_at?->toIso8601String(),
            ])->all(),
            hasBlockingLegalCheck: $legalChecks->contains(fn ($check) => $check->blocksClosing()),
            // Bibliothèque active pour la proposition côté parties —
            // l'éligibilité exacte (instrument, forme juridique) reste
            // revérifiée dans l'action, la liste est un affichage.
            clauseLibrary: ContractClauseLibrary::query()
                ->active()
                ->orderBy('title')
                ->get()
                ->map(fn (ContractClauseLibrary $entry) => [
                    'id' => $entry->id,
                    'title' => $entry->title,
                    'category' => $entry->category->value,
                    'variants' => $entry->variants === null ? [] : array_keys($entry->variants),
                ])->all(),
            legalReviewedAt: $workspace->legal_reviewed_at?->toIso8601String(),
            lastActivityAt: $workspace->last_activity_at?->toIso8601String(),
        );
    }

    /**
     * Dernière passe d'une collection de contrôles (la relation
     * porte l'historique intégral) : tri desc sur l'horodatage,
     * filtre du batch winner, remise en ordre stable.
     *
     * @template TModel of \App\Models\FinancialConsistencyCheck|\App\Models\LegalComplianceCheck
     *
     * @param  Collection<int, TModel>  $checks
     * @return Collection<int, TModel>
     */
    private static function latestBatchChecks($checks)
    {
        $latestBatch = $checks
            ->sortByDesc(fn ($check) => [$check->created_at?->timestamp, $check->id])
            ->first()?->batch;

        return $latestBatch === null
            ? collect()
            : $checks
                ->filter(fn ($check) => $check->batch === $latestBatch)
                ->sortBy('id')
                ->values();
    }

    /**
     * CamelCase de l'instantané persisté — clés manquantes = null
     * (un instantané partiel reste affichable, jamais complété à la
     * main).
     *
     * @param  array<string, mixed>|null  $snapshot
     * @return array<string, mixed>|null
     */
    private static function capTableSnapshot(?array $snapshot, string $currency): ?array
    {
        if ($snapshot === null) {
            return null;
        }

        $entries = $snapshot['entries'] ?? null;

        if (! is_array($entries)) {
            $entries = [];
        }

        return [
            'mechanism' => $snapshot['mechanism'] ?? null,
            'currency' => $snapshot['currency'] ?? $currency,
            'preMoneyMinor' => $snapshot['pre_money_minor'] ?? null,
            'postMoneyMinor' => $snapshot['post_money_minor'] ?? null,
            'capitalAmountMinor' => $snapshot['capital_amount_minor'] ?? null,
            'shareCountBefore' => $snapshot['share_count_before'] ?? null,
            'newShares' => $snapshot['new_shares'] ?? null,
            'shareCountAfter' => $snapshot['share_count_after'] ?? null,
            'pricePerShareMinor' => $snapshot['price_per_share_minor'] ?? null,
            'pricePerShareExact' => $snapshot['price_per_share_exact'] ?? null,
            'investorShareBps' => $snapshot['investor_share_bps'] ?? null,
            'founderShareBps' => $snapshot['founder_share_bps'] ?? null,
            'targetInvestorShareBps' => $snapshot['target_investor_share_bps'] ?? null,
            'targetAchievable' => $snapshot['target_achievable'] ?? null,
            'verifiedData' => $snapshot['verified_data'] ?? null,
            'entries' => collect($entries)->map(fn ($entry) => [
                'holderName' => $entry['holder_name'] ?? null,
                'holderType' => $entry['holder_type'] ?? null,
                'isFounder' => $entry['is_founder'] ?? false,
                'isInvestor' => $entry['is_investor'] ?? false,
                'sharesBefore' => $entry['shares_before'] ?? null,
                'sharesAfter' => $entry['shares_after'] ?? null,
                'percentageBeforeBps' => $entry['percentage_before_bps'] ?? null,
                'percentageAfterBps' => $entry['percentage_after_bps'] ?? null,
            ])->all(),
            'warnings' => $snapshot['warnings'] ?? [],
        ];
    }
}
