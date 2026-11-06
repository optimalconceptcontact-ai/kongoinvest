<?php

use App\Actions\Contracts\CreateInvestmentContractWorkspace;
use App\Actions\Contracts\RecordAnalysisConsent;
use App\Contracts\NegotiationAnalysisProviderInterface;
use App\Enums\AiInteractionType;
use App\Enums\AiRequestStatus;
use App\Enums\ClauseCategory;
use App\Enums\ClauseOrigin;
use App\Enums\ClauseStatus;
use App\Enums\LegalForm;
use App\Enums\NegotiationTermStatus;
use App\Enums\TermSource;
use App\Models\AiUsage;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\CompanyLegalRecord;
use App\Models\ContractClauseLibrary;
use App\Models\ContractClauseSelection;
use App\Models\ContractFinancingComponent;
use App\Models\Conversation;
use App\Models\DealRoom;
use App\Models\InvestmentContractWorkspace;
use App\Models\Message;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Ai\NegotiationAnalysisResult;
use App\Services\Ai\NegotiationAnalysisService;
use App\Services\SubscriptionService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SubscriptionPlansSeeder;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(SubscriptionPlansSeeder::class);
});

// Helpers nommés de façon unique pour cohabiter avec les autres
// fichiers de tests dans un même run de la suite.

function negotiationPlan(string $key): SubscriptionPlan
{
    return SubscriptionPlan::query()->where('key', $key)->firstOrFail();
}

function negotiationRoom(): DealRoom
{
    $entrepreneur = User::factory()->entrepreneur()->create();
    $investor = User::factory()->investor()->create();

    return DealRoom::factory()->create([
        'entrepreneur_user_id' => $entrepreneur->id,
        'investor_user_id' => $investor->id,
    ]);
}

function negotiationWorkspace(DealRoom $room): InvestmentContractWorkspace
{
    app(SubscriptionService::class)->switchPlan($room->entrepreneur, negotiationPlan('entrepreneur'));

    $company = Company::factory()->create();
    CompanyLegalRecord::factory()->create([
        'company_id' => $company->id,
        'legal_form' => LegalForm::Sarlu->value,
        'is_current' => true,
    ]);

    return app(CreateInvestmentContractWorkspace::class)
        ->handle($room->entrepreneur, $room, ['company_id' => $company->id]);
}

function negotiationConsents(DealRoom $room, InvestmentContractWorkspace $workspace): void
{
    app(RecordAnalysisConsent::class)->handle($room->entrepreneur, $workspace);
    app(RecordAnalysisConsent::class)->handle($room->investor, $workspace);
}

/**
 * @param  array<int, array{sender: string, body: string}>  $lines
 * @return array<int, Message>
 */
function negotiationConversation(DealRoom $room, array $lines): array
{
    $conversation = Conversation::factory()->create(['deal_room_id' => $room->id]);

    return collect($lines)
        ->map(fn ($line) => Message::factory()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $line['sender'] === 'entrepreneur' ? $room->entrepreneur_user_id : $room->investor_user_id,
            'body' => $line['body'],
        ]))
        ->each(fn (Message $message, $index) => $message->forceFill(['created_at' => now()->subMinutes(10 - $index)])->save())
        ->all();
}

function negotiationClause(array $overrides = []): ContractClauseLibrary
{
    $attributes = [
        'slug' => 'clause-'.fake()->unique()->slug(),
        'title' => 'Clause de test',
        'category' => ClauseCategory::MixedCoordination->value,
        'purpose' => 'Coordonner capital et dette pour cette opération de test.',
        'plain_language_explanation' => 'Explication en langage simple.',
        'founder_benefit' => 'Bénéfice fondateur.',
        'investor_benefit' => 'Bénéfice investisseur.',
        'financial_impact' => 'Impact financier non évalué durant le test.',
        'legal_impact' => 'Impact juridique non évalué durant le test.',
        'risks' => 'Aucun risque réaliste pour un test.',
        'body' => 'Corps contractuel de base ({{investor_share_bps}}).',
        'is_active' => true,
    ];

    return ContractClauseLibrary::create(array_merge($attributes, $overrides));
}

/**
 * @return array<string, mixed> ligne « conditions » au format du DTO revalidé
 */
function negotiationTerm(array $overrides = []): array
{
    return array_merge([
        'subject' => 'loan_rate_bps',
        'label' => 'Taux d’intérêt de la dette',
        'category' => 'financial',
        'kind' => 'interest_rate',
        'instrument' => 'debt',
        'value' => ['bps' => 1200],
        'display_value' => '12 % par an',
        'status' => 'counter_proposal',
        'source_index' => null,
    ], $overrides);
}

function negotiationProvider(?NegotiationAnalysisResult $result): NegotiationAnalysisProviderInterface
{
    return new class($result) implements NegotiationAnalysisProviderInterface
    {
        public function __construct(private readonly ?NegotiationAnalysisResult $result) {}

        public function analyze(
            InvestmentContractWorkspace $workspace,
            string $conversationTranscript,
            string $clauseCatalog,
            string $operationSummary,
        ): ?NegotiationAnalysisResult {
            return $this->result;
        }
    };
}

function negotiationBind(?NegotiationAnalysisResult $result, ?stdClass $holder = null): void
{
    app()->bind(NegotiationAnalysisProviderInterface::class, function () use ($result, $holder) {
        if ($holder !== null) {
            return new class($result, $holder) implements NegotiationAnalysisProviderInterface
            {
                public function __construct(
                    private readonly ?NegotiationAnalysisResult $result,
                    private readonly stdClass $holder,
                ) {}

                public function analyze(
                    InvestmentContractWorkspace $workspace,
                    string $conversationTranscript,
                    string $clauseCatalog,
                    string $operationSummary,
                ): ?NegotiationAnalysisResult {
                    $this->holder->transcript = $conversationTranscript;
                    $this->holder->catalog = $clauseCatalog;
                    $this->holder->summary = $operationSummary;

                    return $this->result;
                }
            };
        }

        return negotiationProvider($result);
    });
}

/**
 * @param  list<array<string, mixed>>  $terms
 * @param  list<array{library_slug: string, recommended_variant: ?string, rationale: string}>  $clauses
 */
function negotiationTokens(string $summary, array $terms, array $clauses, int $tokens = 500): NegotiationAnalysisResult
{
    return new NegotiationAnalysisResult($summary, $terms, $clauses, $tokens);
}

function negotiationAttemptMessage(User $user, InvestmentContractWorkspace $workspace): ?string
{
    try {
        app(NegotiationAnalysisService::class)->analyze($user, $workspace);

        return null;
    } catch (ValidationException $exception) {
        return collect($exception->errors())->flatten()->implode(' ');
    }
}

it('revalidates model-controlled output server-side before anything is trusted', function () {
    $result = NegotiationAnalysisResult::fromDecoded([
        'summary' => 'Synthèse plausible.',
        'terms' => [
            ['subject' => 'orphan', 'label' => ''], // sans libellé -> jetée
            negotiationTerm(['status' => 'formalized']), // interdit à l'IA -> à clarifier
            negotiationTerm([
                'subject' => 'Participant Share Projet', // normalisé -> participant_share_projet
                'category' => 'écolo',
                'kind' => 'magique',
                'instrument' => 'croisière',
                'value' => ['bps' => 25000, 'amount_minor' => -5, 'months' => 3000, 'text' => 'tiers souples'],
                'source_index' => 0,
            ]),
        ],
        'clause_recommendations' => [
            ['library_slug' => 'Wave Broad', 'recommended_variant' => 'x', 'rationale' => 'espace -> hors format -> jetée'],
            ['library_slug' => 'wav-broad', 'recommended_variant' => 'Wave large', 'rationale' => 'valide'],
            ['library_slug' => 'inventée!!', 'rationale' => 'jetée'],
        ],
    ], -7);

    expect($result->terms)->toHaveCount(2)
        ->and($result->terms[0]['status'])->toBe(NegotiationTermStatus::ToClarify->value)
        ->and($result->terms[1]['subject'])->toBe('participant_share_projet')
        ->and($result->terms[1]['category'])->toBe('other')
        ->and($result->terms[1]['kind'])->toBe('other')
        ->and($result->terms[1]['instrument'])->toBeNull()
        ->and($result->terms[1]['value']['bps'])->toBe(10000)
        ->and($result->terms[1]['value']['amount_minor'])->toBe(0)
        ->and($result->terms[1]['value']['months'])->toBe(1200)
        ->and($result->terms[1]['value']['text'])->toBe('tiers souples')
        ->and($result->terms[1]['source_index'])->toBeNull()
        ->and($result->clauseRecommendations)->toHaveCount(1)
        ->and($result->clauseRecommendations[0]['library_slug'])->toBe('wav-broad')
        ->and($result->clauseRecommendations[0]['recommended_variant'])->toBe('Wave large')
        ->and($result->tokensUsed)->toBe(0);

    // Une sortie vide n'est pas une analyse : null, jamais une fabrication.
    expect(NegotiationAnalysisResult::fromDecoded([], 100))->toBeNull();
});

it('refuses to analyse before both parties consent', function () {
    $room = negotiationRoom();
    $workspace = negotiationWorkspace($room);

    negotiationConversation($room, [
        ['sender' => 'entrepreneur', 'body' => 'Nous visons 300 000 USD.'],
    ]);

    // Aucun consentement du tout.
    expect(negotiationAttemptMessage($room->investor, $workspace))->toContain('DEUX parties');

    // Un seul côté ne suffit jamais (§14).
    $workspace = app(RecordAnalysisConsent::class)->handle($room->entrepreneur, $workspace);
    expect(negotiationAttemptMessage($room->investor, $workspace))->toContain('DEUX parties');

    expect($workspace->snapshots()->count())->toBe(0);
    expect(AiUsage::query()->where('type', AiInteractionType::NegotiationAnalysis->value)->count())->toBe(0);
});

it('refuses analysis from a non-participant even with both consents', function () {
    $room = negotiationRoom();
    $workspace = negotiationWorkspace($room);
    negotiationConsents($room, $workspace);

    expect(negotiationAttemptMessage(User::factory()->create(), $workspace))->toContain('participants');
});

it('refuses analysis once the operation is closed or cancelled', function () {
    $room = negotiationRoom();
    $workspace = negotiationWorkspace($room);
    negotiationConsents($room, $workspace);

    $workspace->forceFill(['status' => 'closed', 'closed_at' => now()])->saveQuietly();
    expect(negotiationAttemptMessage($room->investor, $workspace))->toContain('clôturée');

    $workspace->forceFill(['status' => 'cancelled', 'closed_at' => null])->saveQuietly();
    expect(negotiationAttemptMessage($room->investor, $workspace))->toContain('annulée');
});

it('enforces the monthly AI token quota before reaching the provider', function () {
    $room = negotiationRoom();
    $workspace = negotiationWorkspace($room);
    negotiationConsents($room, $workspace);

    $called = new stdClass;
    $called->hit = false;
    $result = negotiationTokens('x', [], []);
    app()->bind(NegotiationAnalysisProviderInterface::class, function () use ($result, $called) {
        return new class($result, $called) implements NegotiationAnalysisProviderInterface
        {
            public function __construct(private readonly ?NegotiationAnalysisResult $result, private readonly stdClass $called) {}

            public function analyze(InvestmentContractWorkspace $workspace, string $a, string $b, string $c): ?NegotiationAnalysisResult
            {
                $this->called->hit = true;

                return $this->result;
            }
        };
    });

    // Simule un mois déjà consommé au plafond du plan entrepreneur.
    $quota = app(SubscriptionService::class)->limit($room->entrepreneur, 'ai_monthly_token_quota');
    expect($quota)->toBeInt();
    AiUsage::create([
        'user_id' => $room->entrepreneur_user_id,
        'type' => AiInteractionType::ProjectAnalysis->value,
        'status' => AiRequestStatus::Completed->value,
        'tokens_used' => $quota,
        'created_at' => now(),
    ]);

    expect(negotiationAttemptMessage($room->entrepreneur, $workspace))->toContain('quota mensuel');
    expect($called->hit)->toBeFalse();
    expect($workspace->snapshots()->count())->toBe(0);
});

it('writes a versioned snapshot with verifiable coverage, terms and conflicts', function () {
    $room = negotiationRoom();
    $workspace = negotiationWorkspace($room);
    negotiationConsents($room, $workspace);

    // Composantes du scénario de référence : capital + dette — la
    // synthèse doit rester explicite sur la dette non dilutive (§7, §16 r.5).
    ContractFinancingComponent::factory()->create(['investment_contract_workspace_id' => $workspace->id]);
    ContractFinancingComponent::factory()->debt()->create(['investment_contract_workspace_id' => $workspace->id]);

    [$m1, $m2, $m3] = negotiationConversation($room, [
        ['sender' => 'entrepreneur', 'body' => 'Nous cherchons 250 000 USD pour consolider la trésorerie.'],
        ['sender' => 'investor', 'body' => 'Le taux de référence serait 12 % par an sur la dette.'],
        ['sender' => 'entrepreneur', 'body' => 'Nous partageons 25 % du capital pour cet apport.'],
    ]);

    negotiationClause([
        'slug' => 'antidilution',
        'title' => 'Clause antidilution',
        'variants' => [
            ['key' => 'wav_broad', 'brief' => 'Large pondérée', 'body' => 'Corps ajusté pondéré large.'],
            ['key' => 'wav_narrow', 'brief' => 'Étroite', 'body' => 'Corps ajusté étroit.'],
        ],
    ]);

    negotiationBind(negotiationTokens(
        'Consensus provisoire : dette 150k à 12 %, capital à clarifier.',
        [
            negotiationTerm(['source_index' => 2]), // [Message 2] réel -> mappé à m2
            negotiationTerm([
                'subject' => 'investor_share',
                'label' => 'Part de l’investisseur',
                'kind' => 'percentage',
                'instrument' => 'capital',
                'value' => ['bps' => 2500],
                'display_value' => '25 %',
                'status' => 'under_discussion',
                'source_index' => 3,
            ]),
            negotiationTerm([ // version rivale divergente, jamais arbitraire
                'subject' => 'investor_share',
                'label' => 'Part de l’investisseur',
                'kind' => 'percentage',
                'instrument' => 'capital',
                'value' => ['bps' => 3000],
                'display_value' => '30 %',
                'status' => 'initial_proposal',
                'source_index' => 99, // hors couverture -> sans mapping
            ]),
            negotiationTerm(), // doublon exact de la première ligne -> collapsé
        ],
        [['library_slug' => 'antidilution', 'recommended_variant' => 'wav_broad', 'rationale' => 'L’opération mêle capital et dette : un ajustement antidilution s’impose.']],
    ), $holder = new stdClass);

    $snapshot = app(NegotiationAnalysisService::class)->analyze($room->investor, $workspace);

    expect($snapshot->version)->toBe(1)
        ->and($snapshot->status)->toBe(AiRequestStatus::Completed)
        ->and($snapshot->created_by_user_id)->toBe($room->investor_user_id)
        ->and($snapshot->coverage['message_ids'])->toBe([$m1->id, $m2->id, $m3->id])
        ->and($snapshot->coverage['message_count'])->toBe(3)
        ->and($snapshot->coverage['consent']['entrepreneur_consent_at'])->not->toBeNull()
        ->and($snapshot->coverage['consent']['investor_consent_at'])->not->toBeNull()
        ->and($snapshot->provider)->toBe(config('ai.features.provider'))
        ->and($snapshot->tokens_used)->toBe(500);

    // Contexte passé au modèle : transcript indexé, catalogue digest
    // limité aux clauses actives, synthèse d'opération distinguant
    // nettement capital et dette (§16 r.5).
    expect($holder->transcript)->toContain('[Message 2]')
        ->and($holder->transcript)->toContain('Investisseur')
        ->and($holder->transcript)->toContain('taux de référence serait 12 %')
        ->and($holder->catalog)->toContain('[antidilution]')
        ->and($holder->summary)->toContain('dette (NON dilutive)');

    $terms = $workspace->terms()->orderBy('id')->get();
    expect($terms)->toHaveCount(3); // doublon exact collapsé

    $rate = $terms->where('subject', 'loan_rate_bps')->sole();
    expect($rate->status)->toBe(NegotiationTermStatus::CounterProposal)
        ->and($rate->source)->toBe(TermSource::Conversation)
        ->and($rate->source_message_id)->toBe($m2->id)
        ->and($rate->source_context['author_role'])->toBe('Investisseur')
        ->and($rate->source_context['author_name'])->toBe($m2->sender->name)
        ->and($rate->value)->toBe(['bps' => 1200]);

    $shareVersions = $terms->where('subject', 'investor_share')->values();
    expect($shareVersions)->toHaveCount(2)
        // Toutes deux marquées contradictoires : jamais arbitrées.
        ->and($shareVersions[0]->status)->toBe(NegotiationTermStatus::Contradictory)
        ->and($shareVersions[1]->status)->toBe(NegotiationTermStatus::Contradictory)
        ->and($shareVersions[0]->notes)->toContain('divergentes')
        ->and($shareVersions[0]->conflict_with_term_id)->toBeNull()
        ->and($shareVersions[1]->conflict_with_term_id)->toBe($shareVersions[0]->id)
        ->and($shareVersions[1]->source_message_id)->toBeNull(); // index 99 hors périmètre

    $selection = $workspace->clauseSelections()->sole();
    expect($selection->title)->toBe('Clause antidilution')
        ->and($selection->status)->toBe(ClauseStatus::ProposedByAi)
        ->and($selection->origin)->toBe(ClauseOrigin::Ai)
        ->and($selection->variant)->toBe('wav_broad')
        ->and($selection->body)->toBe('Corps ajusté pondéré large.')
        ->and($selection->snapshot_id)->toBe($snapshot->id);

    expect($snapshot->synthesis['counts'])->toBe([
        'terms_kept' => 3,
        'terms_with_conflicts' => 1,
        'clause_recommendations_kept' => 1,
        'clause_recommendations_ignored' => 0,
    ]);

    // Usage tracé + audit de l'analyse (§49, §112).
    expect(AiUsage::query()
        ->where('user_id', $room->investor_user_id)
        ->where('type', AiInteractionType::NegotiationAnalysis->value)
        ->where('status', AiRequestStatus::Completed->value)
        ->where('tokens_used', 500)
        ->exists())->toBeTrue()
        ->and(AuditLog::query()->where('action', 'workspace.ai_analysis')->where('subject_id', $snapshot->id)->exists())->toBeTrue();

    // Une relance crée une version suivante, jamais une surcharge ;
    // la clause déjà proposée n'est pas duppliquée.
    $second = app(NegotiationAnalysisService::class)->analyze($room->entrepreneur, $workspace);
    expect($second->version)->toBe(2)
        ->and(ContractClauseSelection::query()->where('investment_contract_workspace_id', $workspace->id)->count())->toBe(1);
});

it('incorporates real clauses only and records the ignored recommendations', function () {
    $room = negotiationRoom();
    $workspace = negotiationWorkspace($room);
    negotiationConsents($room, $workspace);

    ContractFinancingComponent::factory()->create(['investment_contract_workspace_id' => $workspace->id]); // capital (scénario par défaut)

    negotiationClause(['slug' => 'extraction-convertible', 'instruments' => ['convertible'], 'title' => 'Exclusion convertible']);
    negotiationClause(['slug' => 'veto-sa', 'legal_forms' => ['sa'], 'title' => 'Veto S.A.']);
    $already = negotiationClause(['slug' => 'idem-prix', 'title' => 'Idem prix']);

    ContractClauseSelection::forceCreate([ // déjà proposée avant le run : ignorée silencieusement
        'investment_contract_workspace_id' => $workspace->id,
        'contract_clause_library_id' => $already->id,
        'title' => 'Idem prix',
        'category' => ClauseCategory::Capital->value,
        'origin' => ClauseOrigin::Founder->value,
        'status' => ClauseStatus::ProposedByFounder->value,
        'body' => 'Corps initial fondateur.',
        'is_custom' => false,
        'sort_order' => 1,
    ]);

    negotiationBind(negotiationTokens(
        'Clauses candidates filtrées côté serveur.',
        [],
        [
            ['library_slug' => 'extraction-convertible', 'recommended_variant' => null, 'rationale' => 'hors instruments présents'],
            ['library_slug' => 'veto-sa', 'recommended_variant' => null, 'rationale' => 'hors forme juridique'],
            ['library_slug' => 'idem-prix', 'recommended_variant' => null, 'rationale' => 'déjà proposée'],
            ['library_slug' => 'clause-inventee-nulle-part', 'recommended_variant' => null, 'rationale' => 'inconnue de la bibliothèque'],
        ],
    ));

    $snapshot = app(NegotiationAnalysisService::class)->analyze($room->investor, $workspace);

    expect($workspace->clauseSelections()->where('snapshot_id', $snapshot->id)->count())->toBe(0)
        ->and($snapshot->synthesis['counts']['clause_recommendations_kept'])->toBe(0)
        ->and($snapshot->synthesis['ignored_recommendations'])->toHaveCount(3);

    $ignored = collect($snapshot->synthesis['ignored_recommendations']);
    expect($ignored->first(fn ($row) => str_contains($row, 'extraction-convertible')))->toContain('instruments non présents')
        ->and($ignored->first(fn ($row) => str_contains($row, 'veto-sa')))->toContain('SARLU')
        ->and($ignored->first(fn ($row) => str_contains($row, 'clause-inventee-nulle-part')))->toContain('aucune clause inventée');

    // L'idem-pix « déjà proposée » est silencieuse : aucune raison tracée.
    expect($ignored->first(fn ($row) => str_contains($row, 'idem-prix')))->toBeNull();

    // Deux recommandations applicables : l'une avec une variante réelle
    // de la bibliothèque, l'autre avec une variante inconnue — le corps
    // retombe au texte de base de la clause, jamais à une invention IA.
    $dragAlong = negotiationClause([
        'slug' => 'drag-along',
        'title' => 'Sort forcé',
        'body' => 'Corps de base drag along.',
        'variants' => [['key' => 'allez-jardin', 'body' => 'Corps variante jardin.']],
    ]);
    $tagAlong = negotiationClause([
        'slug' => 'tag-along',
        'title' => 'Droite de sortie conjointe',
        'body' => 'Corps de base tag along.',
        'variants' => [['key' => 'surprise', 'body' => 'Corps variante surprise.']],
    ]);

    negotiationBind(negotiationTokens('Réexécution ciblée.', [], [
        ['library_slug' => 'drag-along', 'recommended_variant' => 'allez-jardin', 'rationale' => 'variante documentée'],
        ['library_slug' => 'tag-along', 'recommended_variant' => 'variante-qui-n-existe-pas', 'rationale' => 'variante inventée écartée'],
    ]));

    $second = app(NegotiationAnalysisService::class)->analyze($room->investor, $workspace);
    expect($second->synthesis['counts']['clause_recommendations_kept'])->toBe(2);

    $selections = $workspace->clauseSelections()->where('snapshot_id', $second->id)->get();

    $knownVariant = $selections->firstWhere('contract_clause_library_id', $dragAlong->id);
    expect($knownVariant)->not->toBeNull()
        ->and($knownVariant->variant)->toBe('allez-jardin')
        ->and($knownVariant->body)->toBe('Corps variante jardin.');

    $unknownVariant = $selections->firstWhere('contract_clause_library_id', $tagAlong->id);
    expect($unknownVariant)->not->toBeNull()
        ->and($unknownVariant->variant)->toBeNull()
        ->and($unknownVariant->body)->toBe('Corps de base tag along.');
});

it('records a failed analysis honestly and leaves the retry available', function () {
    $room = negotiationRoom();
    $workspace = negotiationWorkspace($room);
    negotiationConsents($room, $workspace);

    negotiationBind(null); // provider indisponible (Null provider ou payload inutilisable)

    expect(negotiationAttemptMessage($room->entrepreneur, $workspace))->toContain('pas pu être produite');

    $snapshot = $workspace->snapshots()->sole();
    expect($snapshot->status)->toBe(AiRequestStatus::Failed)
        ->and($snapshot->failure_reason)->toContain('aucune analyse n\'est fabriquée')
        ->and($snapshot->synthesis)->toBeNull()
        ->and($snapshot->completed_at)->toBeNull();

    // Échec tracé dans les usages (§112) — aucun terme fabriqué.
    expect(AiUsage::query()
        ->where('type', AiInteractionType::NegotiationAnalysis->value)
        ->where('status', AiRequestStatus::Failed->value)
        ->exists())->toBeTrue()
        ->and(AiUsage::query()->where('status', AiRequestStatus::Completed->value)->count())->toBe(0)
        ->and($workspace->terms()->count())->toBe(0)
        ->and($workspace->clauseSelections()->count())->toBe(0);

    // Relance de reprise : disponible, version suivante, sans surcharge.
    negotiationBind(negotiationTokens('Reprise réussie.', [negotiationTerm()], []));
    $relaunched = app(NegotiationAnalysisService::class)->analyze($room->entrepreneur, $workspace);
    expect($relaunched->status)->toBe(AiRequestStatus::Completed)
        ->and($relaunched->version)->toBe(2)
        ->and($workspace->snapshots()->count())->toBe(2);
});
