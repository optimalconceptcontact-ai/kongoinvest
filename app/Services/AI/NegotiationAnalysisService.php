<?php

namespace App\Services\Ai;

use App\Contracts\NegotiationAnalysisProviderInterface;
use App\Enums\AiInteractionType;
use App\Enums\AiRequestStatus;
use App\Enums\ClauseOrigin;
use App\Enums\ClauseStatus;
use App\Enums\FinancingComponentType;
use App\Enums\LegalForm;
use App\Enums\NegotiationTermStatus;
use App\Enums\TermSource;
use App\Enums\WorkspaceStatus;
use App\Models\CompanyLegalRecord;
use App\Models\ContractClauseLibrary;
use App\Models\ContractClauseSelection;
use App\Models\InvestmentContractWorkspace;
use App\Models\Message;
use App\Models\NegotiationConversationSnapshot;
use App\Models\NegotiationTerm;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Analyse IA des conversations de négociation d'un espace
 * contractuel (prompt §4, §5, §13) :
 *
 * - JAMAIS sans les DEUX consentements tracés (prompt §14) —
 *   RecordAnalysisConsent les enregistre, ce service les lit.
 * - Chaque analyse écrit un instantané versionné qui référence
 *   exactement les messages utilisés et les consentements actifs :
 *   chaque information extraite reste vérifiable à sa source.
 * - Clauses recommandées : UNIQUEMENT des items de la bibliothèque
 *   structurée (slug existant, applicabilité instrument / forme
 *   juridique) — jamais une clause sortie du modèle. Les parties
 *   décident ensuite.
 * - Une simple discussion n'est jamais un accord : les conditions
 *   de statut non contraignant ne se transforment jamais
 *   automatiquement en engagements.
 */
class NegotiationAnalysisService
{
    public function __construct(
        private NegotiationAnalysisProviderInterface $provider,
        private AiUsageService $usage,
        private AuditLogger $audit,
    ) {}

    public function analyze(User $requester, InvestmentContractWorkspace $workspace): NegotiationConversationSnapshot
    {
        if (! $workspace->isParticipant($requester)) {
            throw ValidationException::withMessages([
                'workspace' => "Seuls les participants de l'opération peuvent demander une analyse.",
            ]);
        }

        // Le double consentement est un prérequis ABSOLU (prompt
        // §14) : jamais d'analyse sans les deux consentements enregistrés.
        if (! $workspace->conversationAnalysisConsented()) {
            throw ValidationException::withMessages([
                'consent' => "L'analyse exige le consentement explicite des DEUX parties (enregistré depuis l'espace de négociation) avant d'être disponible.",
            ]);
        }

        if ($workspace->isClosed() || $workspace->status === WorkspaceStatus::Cancelled) {
            throw ValidationException::withMessages([
                'workspace' => "L'opération est clôturée ou annulée : plus d'analyse disponible.",
            ]);
        }

        $this->usage->assertWithinQuota($requester);

        $workspace->loadMissing('dealRoom.conversation.messages');

        // Périmètre borné : les 50 derniers messages triés — la MÊME
        // collection alimente le transcript, la couverture et le
        // rattachement des sources (aucun écart d'index possible).
        $messages = collect($workspace->dealRoom->conversation?->messages ?? [])
            ->sortBy('created_at')
            ->take(-50)
            ->values();

        [$transcript, $messageIds] = $this->buildTranscript($workspace, $messages);

        // Instantané avant appel : périmètre exact + consentements
        // du moment. Une relance crée une version suivante, jamais
        // une surcharge.
        $version = ((int) NegotiationConversationSnapshot::query()
            ->where('investment_contract_workspace_id', $workspace->id)
            ->max('version')) + 1;

        $snapshot = NegotiationConversationSnapshot::forceCreate([
            'investment_contract_workspace_id' => $workspace->id,
            'created_by_user_id' => $requester->id,
            'version' => $version,
            'status' => AiRequestStatus::Pending->value,
            'coverage' => [
                'message_ids' => $messageIds,
                'first_message_id' => $messageIds[0] ?? null,
                'last_message_id' => $messageIds[count($messageIds) - 1] ?? null,
                'message_count' => count($messageIds),
                'consent' => [
                    'entrepreneur_consent_at' => $workspace->entrepreneur_analysis_consent_at?->toIso8601String(),
                    'investor_consent_at' => $workspace->investor_analysis_consent_at?->toIso8601String(),
                ],
            ],
        ]);

        $catalog = $this->buildClauseCatalog();
        $operationSummary = $this->buildOperationSummary($workspace);

        $result = $this->provider->analyze($workspace, $transcript, $catalog, $operationSummary);

        if ($result === null) {
            $this->usage->record($requester, AiInteractionType::NegotiationAnalysis, AiRequestStatus::Failed, $workspace->dealRoom->project);

            $snapshot->forceFill([
                'status' => AiRequestStatus::Failed->value,
                'failure_reason' => "L'analyse IA n'a pas abouti. La relance reste disponible — aucune analyse n'est fabriquée.",
            ])->save();

            throw ValidationException::withMessages([
                'ai' => "L'analyse de la négociation n'a pas pu être produite. Relancez dans quelques instants.",
            ]);
        }

        [$keptTerms, $conflicts] = $this->persistTerms($workspace, $snapshot, $result, $messages);
        $keptClauses = $this->persistClauseRecommendations($workspace, $snapshot, $result, $requester);

        $providerName = config('ai.features.provider');
        $snapshot->forceFill([
            'status' => AiRequestStatus::Completed->value,
            'provider' => $providerName,
            'model' => config('ai.features.model'),
            'tokens_used' => $result->tokensUsed,
            'synthesis' => [
                'summary' => $result->summary,
                'terms' => $keptTerms,
                'clause_recommendations' => $result->clauseRecommendations,
                'counts' => [
                    'terms_kept' => count($keptTerms),
                    'terms_with_conflicts' => $conflicts,
                    'clause_recommendations_kept' => $keptClauses['kept'],
                    'clause_recommendations_ignored' => count($keptClauses['ignored']),
                ],
                'ignored_recommendations' => $keptClauses['ignored'],
            ],
            'completed_at' => now(),
        ])->save();

        $this->usage->record($requester, AiInteractionType::NegotiationAnalysis, AiRequestStatus::Completed, $workspace->dealRoom->project, $providerName, $result->tokensUsed);

        $this->audit->log($requester, 'workspace.ai_analysis', $snapshot, [
            'workspace_id' => $workspace->id,
            'version' => $version,
            'terms_kept' => count($keptTerms),
            'clauses_proposed' => $keptClauses['kept'],
            'tokens_used' => $result->tokensUsed,
        ]);

        $workspace->forceFill(['last_activity_at' => now()])->save();

        return $snapshot->fresh();
    }

    /**
     * Transcript désinfecté et INDEXÉ : chaque bloc porte un index
     * 1..n aligné sur coverage.message_ids — l'IA ne citant que cet
     * index, la traçabilité remonte au message exact (prompt §4).
     *
     * @param  Collection<int, Message>  $messages
     * @return array{0: string, 1: list<int>}
     */
    private function buildTranscript(InvestmentContractWorkspace $workspace, Collection $messages): array
    {
        if ($messages->isEmpty()) {
            return ["(Aucun message échangé pour l'instant dans cet espace.)", []];
        }

        // Party-supplied content is untrusted: delimited, labeled,
        // capped — one side can neither smuggle instructions nor
        // flood the agent context (même discipline que
        // ContractDraftService).
        $ids = $messages->map(fn (Message $message): int => $message->getKey())->values()->all();
        $ids = array_values($ids); // liste séquence exacte alignée sur coverage.message_ids
        $pad = strlen((string) count($ids));

        $blocks = $messages->map(function (Message $message, int $index) use ($workspace, $pad) {
            $role = $message->sender_id === $workspace->dealRoom->entrepreneur_user_id ? 'Entrepreneur' : 'Investisseur';
            $body = mb_substr((string) $message->body, 0, 1000);

            return '[Message '.str_pad((string) ($index + 1), $pad, '0', STR_PAD_LEFT)."]: {$role} ({$message->sender->name}, le {$message->created_at->toDayDateTimeString()}) — propos non vérifiés\n{$body}";
        });

        return [$blocks->implode("\n---\n"), $ids];
    }

    /**
     * Catalogue digest des clauses actives : identités + bénéfices
     * (pas le texte contractuel complet). L'IA recommande des slugs ;
     * elle ne réécrit jamais les clauses (prompt §5).
     */
    private function buildClauseCatalog(): string
    {
        $items = ContractClauseLibrary::query()->active()->orderBy('slug')->get();

        if ($items->isEmpty()) {
            return '(Bibliothèque de clauses vide — aucune recommandation possible.)';
        }

        return $items
            ->map(function (ContractClauseLibrary $clause) {
                // Variante du catalogue : la structure fait foi — les
                // lignes malformées sont écartées plutôt que masquées.
                $variants = collect(is_array($clause->variants) ? $clause->variants : [])
                    ->filter(fn ($variant) => is_array($variant) && filled($variant['key'] ?? null))
                    ->map(fn ($variant) => $variant['key'].' — '.mb_substr((string) ($variant['brief'] ?? ''), 0, 120))
                    ->implode(', ');

                $lines = collect([
                    'title' => $clause->title,
                    'category' => $clause->category->label(),
                    'purpose' => filled($clause->purpose) ? str($clause->purpose)->limit(300)->toString() : null,
                    'founder_benefit' => filled($clause->founder_benefit) ? str($clause->founder_benefit)->limit(200)->toString() : null,
                    'investor_benefit' => filled($clause->investor_benefit) ? str($clause->investor_benefit)->limit(200)->toString() : null,
                    'variants' => $variants !== '' ? $variants : null,
                    'requires_legal_review' => $clause->requires_legal_review ? 'oui' : 'non',
                ])
                    ->filter()
                    ->map(fn ($value, $label) => "  {$label}: {$value}")
                    ->implode("\n");

                return "[{$clause->slug}]\n{$lines}";
            })
            ->implode("\n\n");
    }

    /**
     * Composantes financières STRICTEMENT distinguées (capital vs
     * dette vs convertible) + structure juridique connue : l'analyse
     * ne peut jamais assimiler dette et fonds propres dans cette
     * synthèse (prompt §16, r.5).
     */
    private function buildOperationSummary(InvestmentContractWorkspace $workspace): string
    {
        $parts = [];

        if ($workspace->total_investment_minor !== null) {
            $parts[] = 'Montant global '.number_format($workspace->total_investment_minor / 100, 0).' '.$workspace->currency;
        }

        foreach ($workspace->financingComponents as $component) {
            $type = $component->type;

            if (! $type instanceof FinancingComponentType) {
                continue; // composante corrompue : jamais interprétée librement
            }

            $label = match ($type) {
                FinancingComponentType::Capital => 'capital (dilutif)',
                FinancingComponentType::Debt => 'dette (NON dilutive)',
                FinancingComponentType::Convertible => 'instrument convertible (conversion non validée — non dilutive ici)',
            };

            $parts[] = $label.($component->amount_minor !== null ? ' '.number_format($component->amount_minor / 100, 0).' '.$component->currency : '');
        }

        return $parts === [] ? '(Aucune composante enregistrée — l\'opération reste à structurer.)' : implode('; ', $parts);
    }

    /**
     * Extraie les conditions du résultat revalidé : dédoublonnage en
     * conservant les versions divergentes du même sujet — statut
     * forcé à « contradictory », toutes tracées, liées entre elles,
     * jamais arbitrées par le service.
     *
     * @param  Collection<int, Message>  $messages
     * @return array{0: list<array<string, mixed>>, 1: int}
     */
    private function persistTerms(InvestmentContractWorkspace $workspace, NegotiationConversationSnapshot $snapshot, NegotiationAnalysisResult $result, Collection $messages): array
    {
        // Dédupe exact + détection des conditions rivales en une fois :
        // deux conditions de même subject ne sont JAMAIS arbitrairement
        // tranchées — elles restent toutes deux tracées et reliées.
        $bySubject = [];
        $lastOfSubject = [];

        foreach ($result->terms as $term) {
            $subject = $term['subject'];
            $previous = $lastOfSubject[$subject] ?? null;

            if ($previous !== null
                && $previous['display_value'] === $term['display_value']
                && $previous['status'] === $term['status']) {
                continue; // doublon exact de l'IA — sans intérêt
            }

            $bySubject[$subject][] = $term;
            $lastOfSubject[$subject] = $term;
        }

        $kept = [];
        $conflictCount = 0;
        $firstModelIdOf = []; // premier modèle persisté par sujet → cible du conflit

        foreach ($bySubject as $subject => $variants) {
            foreach ($variants as $index => $term) {
                $rival = count($variants) > 1;

                $sourceMessageId = null;
                $sourceContext = null;

                // Source vérifiable : l'index émane du modèle, il n'est
                // accepté que s'il pointe un message réellement couvert
                // par l'analyse (prompt §4).
                if (isset($term['source_index']) && isset($messages[$term['source_index'] - 1])) {
                    $message = $messages[$term['source_index'] - 1];
                    $authorRole = $message->sender_id === $workspace->dealRoom->entrepreneur_user_id ? 'Entrepreneur' : 'Investisseur';
                    $sourceMessageId = $message->id;
                    $sourceContext = [
                        'author_role' => $authorRole,
                        'author_name' => $message->sender?->name,
                        'posted_at' => $message->created_at->toIso8601String(),
                        'extract' => mb_substr((string) $message->body, 0, 300),
                    ];
                }

                $model = NegotiationTerm::forceCreate([
                    'investment_contract_workspace_id' => $workspace->id,
                    'snapshot_id' => $snapshot->id,
                    'subject' => $subject,
                    'label' => $term['label'],
                    'category' => $term['category'],
                    'kind' => $term['kind'],
                    'instrument' => $term['instrument'],
                    'value' => $term['value'],
                    'display_value' => $term['display_value'],
                    'status' => $rival ? NegotiationTermStatus::Contradictory->value : $term['status'],
                    'source' => TermSource::Conversation->value,
                    'source_message_id' => $sourceMessageId,
                    'source_context' => $sourceContext,
                    'notes' => $rival && $index === 0
                        ? 'La conversation porte plusieurs versions divergentes de cette condition : une ronde de clarification est nécessaire.'
                        : null,
                    'proposed_by_user_id' => null,
                ]);

                if ($index > 0) {
                    // Rivale liée à la première version du même sujet :
                    // signalée, jamais arbitrée (commentaire du schéma).
                    $model->forceFill(['conflict_with_term_id' => $firstModelIdOf[$subject] ?? null])->save();
                    $conflictCount++;
                }

                $firstModelIdOf[$subject] ??= $model->id;

                $kept[] = $term;
            }
        }

        return [$kept, $conflictCount];
    }

    /**
     * Recommandation de clauses : seules les clauses RÉELLES de la
     * bibliothèque structurée deviennent des ContractClauseSelections
     * (origines AI, statut 'proposed_by_ai'). L'IA a proposé un slug
     * ; le service vérifie l'applicabilité et copie le texte de la
     * bibliothèque — jamais le texte d'un modèle IA.
     *
     * @return array{kept: int, ignored: list<string>}
     */
    private function persistClauseRecommendations(InvestmentContractWorkspace $workspace, NegotiationConversationSnapshot $snapshot, NegotiationAnalysisResult $result, User $requester): array
    {
        $legalForm = $this->currentLegalForm($workspace);
        $components = $workspace->financingComponents;

        $ignored = [];
        $kept = 0;
        $sortOrder = (int) (ContractClauseSelection::query()
            ->where('investment_contract_workspace_id', $workspace->id)
            ->max('sort_order')) + 1;

        foreach ($result->clauseRecommendations as $recommendation) {
            $slug = $recommendation['library_slug'];
            $clause = ContractClauseLibrary::query()->active()->where('slug', $slug)->first();

            if ($clause === null) {
                $ignored[] = "{$slug} : clause inconnue de la bibliothèque — ignorée (aucune clause inventée).";

                continue;
            }

            // Applicabilité instrument : au moins une composante de
            // l'opération doit être éligible. La première composante
            // compatible porte la portée de la sélection.
            $eligibleInstrument = null;

            foreach ($components as $component) {
                $type = $component->type;

                if ($type instanceof FinancingComponentType && $clause->allowsInstrument($type)) {
                    $eligibleInstrument = $type;

                    break;
                }
            }

            $instrumentsRestricted = filled($clause->instruments);

            if ($instrumentsRestricted && $eligibleInstrument === null) {
                $ignored[] = "{$slug} : restreinte aux instruments non présents dans l'opération — ignorée.";

                continue;
            }

            if ($legalForm !== null && ! $clause->allowsLegalForm($legalForm)) {
                $ignored[] = "{$slug} : non applicable à la forme {$legalForm->label()} — ignorée.";

                continue;
            }

            // Déjà proposée dans cette opération : pas de doublon.
            $already = ContractClauseSelection::withTrashed()
                ->where('investment_contract_workspace_id', $workspace->id)
                ->where('contract_clause_library_id', $clause->id)
                ->exists();

            if ($already) {
                continue; // silencieux : la proposition garde son historique
            }

            $body = $clause->body;
            $variantKey = $recommendation['recommended_variant'];
            $variant = $variantKey !== null ? $clause->variant($variantKey) : null;

            if ($variant !== null && filled($variant['body'] ?? null)) {
                $body = $variant['body'];
            } elseif ($variantKey !== null && $variant === null) {
                $variantKey = null; // variante inconnue → texte de base, jamais une variante inventée
            }

            ContractClauseSelection::forceCreate([
                'investment_contract_workspace_id' => $workspace->id,
                'contract_clause_library_id' => $clause->id,
                'library_version' => $clause->current_version,
                'snapshot_id' => $snapshot->id,
                'title' => $clause->title,
                'category' => $clause->category->value,
                'origin' => ClauseOrigin::Ai->value,
                'status' => ClauseStatus::ProposedByAi->value,
                'instrument_scope' => $instrumentsRestricted && $eligibleInstrument !== null ? $eligibleInstrument->value : null,
                'body' => $body,
                'variant' => $variantKey,
                'rationale' => $recommendation['rationale'],
                'is_custom' => false,
                'proposed_by_user_id' => $requester->id,
                'sort_order' => $sortOrder++,
            ]);

            $kept++;
        }

        return ['kept' => $kept, 'ignored' => $ignored];
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

        return $record?->form(); // accesseur déterministe de la maison
    }
}
