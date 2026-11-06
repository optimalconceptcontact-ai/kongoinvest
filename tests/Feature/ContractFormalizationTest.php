<?php

use App\Actions\Contracts\ApproveContractDocument;
use App\Actions\Contracts\CancelOperation;
use App\Actions\Contracts\CloseOperation;
use App\Actions\Contracts\CreateContractCondition;
use App\Actions\Contracts\CreateContractDocument;
use App\Actions\Contracts\CreateInvestmentContractWorkspace;
use App\Actions\Contracts\DepositSignedContractDocument;
use App\Actions\Contracts\LegallyValidateContractDocument;
use App\Actions\Contracts\LegalReviewOperation;
use App\Actions\Contracts\RecordSignatureDeposit;
use App\Actions\Contracts\RequestContractSignatures;
use App\Actions\Contracts\ResolveContractCondition;
use App\Enums\CheckSeverity;
use App\Enums\CheckStatus;
use App\Enums\ContractConditionStatus;
use App\Enums\ContractConditionType;
use App\Enums\ContractDocumentStatus;
use App\Enums\ContractDocumentType;
use App\Enums\FinancialCheckType;
use App\Enums\SignatureMethod;
use App\Enums\SignatureStatus;
use App\Enums\WorkspaceStatus;
use App\Models\AuditLog;
use App\Models\ContractDocument;
use App\Models\ContractDocumentVersion;
use App\Models\ContractSignature;
use App\Models\DealRoom;
use App\Models\FinancialConsistencyCheck;
use App\Models\InvestmentContractWorkspace;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\SubscriptionService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SubscriptionPlansSeeder;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(SubscriptionPlansSeeder::class);
});

// Helpers préfixés « cform » pour cohabiter avec les autres
// fichiers de la suite dans un même run.

function cformSubscribe(User $user, string $planKey): void
{
    app(SubscriptionService::class)->switchPlan(
        $user,
        SubscriptionPlan::query()->where('key', $planKey)->firstOrFail(),
    );
}

function cformWorkspace(): InvestmentContractWorkspace
{
    $entrepreneur = User::factory()->entrepreneur()->create();
    $investor = User::factory()->investor()->create();
    $room = DealRoom::factory()->create([
        'entrepreneur_user_id' => $entrepreneur->id,
        'investor_user_id' => $investor->id,
    ]);
    cformSubscribe($entrepreneur, 'entrepreneur');

    return app(CreateInvestmentContractWorkspace::class)->handle($entrepreneur, $room);
}

function cformDocument(InvestmentContractWorkspace $workspace): ContractDocument
{
    return app(CreateContractDocument::class)->handle(
        $workspace->dealRoom->entrepreneur,
        $workspace,
        [
            'type' => ContractDocumentType::SubscriptionDeed->value,
            'title' => 'Bulletins de souscription',
            'content' => ['montant_minor' => 500_000_00, 'sections' => ['a', 'b']],
            'confidentiality' => 'shared',
        ],
    );
}

/** Document au circuit de validation commerciale complet. */
function cformApprovedDocument(InvestmentContractWorkspace $workspace): ContractDocument
{
    $document = cformDocument($workspace);

    app(ApproveContractDocument::class)->handle($workspace->dealRoom->entrepreneur, $workspace, $document);
    app(ApproveContractDocument::class)->handle($workspace->dealRoom->investor, $workspace, $document);

    return $document->fresh();
}

function cformAdmin(): User
{
    return User::factory()->create()->assignRole('admin');
}

function cformRun(callable $callback): ?string
{
    try {
        $callback();

        return null;
    } catch (ValidationException $exception) {
        return collect($exception->errors())->flatten()->implode(' ');
    }
}

it('deposits a document with version 1 and its sha256 imprint', function () {
    $workspace = cformWorkspace();

    $document = cformDocument($workspace);

    expect($document->status)->toBe(ContractDocumentStatus::InReview)
        ->and($document->current_version)->toBe(1);

    $version = ContractDocumentVersion::query()->where('contract_document_id', $document->id)->first();
    expect($version->version)->toBe(1)
        ->and($version->content['montant_minor'])->toBe(500_000_00)
        ->and($version->hash)->toBe(
            hash('sha256', json_encode(['montant_minor' => 500_000_00, 'sections' => ['a', 'b']], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)),
        )
        ->and($version->created_by_user_id)->toBe($workspace->dealRoom->entrepreneur_user_id)
        ->and(AuditLog::query()->where('action', 'workspace.document_created')->count())->toBe(1);
});

it('routes the double approval circuit and never a free status', function () {
    $workspace = cformWorkspace();
    $document = cformDocument($workspace);

    // Une seule approbation : in_review reste l'état courant —
    // jamais « approved » avec un seul horodatage.
    app(ApproveContractDocument::class)->handle($workspace->dealRoom->entrepreneur, $workspace, $document);
    expect($document->fresh()->status)->toBe(ContractDocumentStatus::InReview)
        ->and($document->fresh()->founder_approved_at)->not->toBeNull()
        ->and($document->fresh()->investor_approved_at)->toBeNull();

    app(ApproveContractDocument::class)->handle($workspace->dealRoom->investor, $workspace, $document);
    $approved = $document->fresh();
    expect($approved->status)->toBe(ContractDocumentStatus::Approved)
        ->and($approved->hasBothApprovals())->toBeTrue();

    // Un document signé ne rouvre pas ; un tiers non participant non plus.
    expect(cformRun(fn () => app(ApproveContractDocument::class)->handle(
        User::factory()->create(),
        $workspace,
        $approved,
    )))->toContain('participants');
});

it('requires the legal validation gate and full approval before the signed deposit', function () {
    $workspace = cformWorkspace();

    // Document approuvé de référence (circuit double complet).
    $document = cformApprovedDocument($workspace);
    $founder = $workspace->dealRoom->entrepreneur;

    // Sans double approbation d'un NOUVEAU document : refus.
    $early = cformDocument($workspace);
    expect(cformRun(fn () => app(DepositSignedContractDocument::class)->handle($founder, $workspace, $early)))
        ->toContain('approuvé par les deux parties');

    // Sans validation juridique : refus.
    expect(cformRun(fn () => app(DepositSignedContractDocument::class)->handle($founder, $workspace, $document->fresh())))
        ->toContain('validé juridiquement');

    // Un non-juriste (même participant) ne valide pas juridiquement.
    expect(cformRun(fn () => app(LegallyValidateContractDocument::class)->handle($founder, $workspace, $document->fresh())))
        ->toContain('gestion juridique');

    $admin = cformAdmin();
    app(LegallyValidateContractDocument::class)->handle($admin, $workspace, $document->fresh());

    app(DepositSignedContractDocument::class)->handle($founder, $workspace, $document->fresh());

    $signed = $document->fresh();
    expect($signed->status)->toBe(ContractDocumentStatus::Signed)
        ->and($signed->signed_deposited_at)->not->toBeNull()
        ->and($signed->legal_validated_by_user_id)->toBe($admin->id);
});

it('records conditions that block the closing until conducted', function () {
    $workspace = cformWorkspace();
    $founder = $workspace->dealRoom->entrepreneur;

    $condition = app(CreateContractCondition::class)->handle($founder, $workspace, [
        'type' => ContractConditionType::Suspensive->value,
        'description' => 'Sel de la collecte, atteindre 500 000 USD.',
        'due_date' => '2026-12-31',
        'document_id' => cformDocument($workspace)->id,
    ]);

    expect($condition->status)->toBe(ContractConditionStatus::Pending)
        ->and($condition->isBlocking())->toBeTrue()
        // Une référence hors opération est refusée, jamais créée.
        ->and(cformRun(fn () => app(CreateContractCondition::class)->handle($founder, $workspace, [
            'type' => ContractConditionType::Covenant->value,
            'description' => 'Référence hors opération doit être refusée.',
            'document_id' => 999999,
        ])))->toContain('n\'appartient pas à cette opération');

    // Un tiers ne résout pas ; une résolution est attribuée.
    expect(cformRun(fn () => app(ResolveContractCondition::class)->handle(
        User::factory()->create(),
        $workspace,
        $condition,
        ['decision' => 'fulfilled'],
    )))->toContain('participants');

    app(ResolveContractCondition::class)->handle($founder, $workspace, $condition, ['decision' => 'fulfilled']);
    $resolved = $condition->fresh();
    expect($resolved->status)->toBe(ContractConditionStatus::Fulfilled)
        ->and($resolved->fulfilled_at)->not->toBeNull()
        ->and($resolved->verified_by_user_id)->toBe($founder->id);

    // Une condition conduite ne se renie plus.
    expect(cformRun(fn () => app(ResolveContractCondition::class)->handle($founder, $workspace, $resolved, ['decision' => 'waived'])))
        ->toContain('ne se renie plus');
});

it('requests signatures per side on the exact version and records the deposit evidence', function () {
    $workspace = cformWorkspace();
    $document = cformApprovedDocument($workspace);
    $founder = $workspace->dealRoom->entrepreneur;

    // Sans double approbation, pas de demande de signature.
    $early = cformDocument($workspace);
    expect(cformRun(fn () => app(RequestContractSignatures::class)->handle($founder, $workspace, $early)))
        ->toContain('approuvé par les deux parties');

    $signatures = app(RequestContractSignatures::class)->handle($founder, $workspace, $document);

    expect($signatures)->toHaveCount(2)
        ->and($document->fresh()->status)->toBe(ContractDocumentStatus::PendingSignature)
        ->and($signatures[0]->signer_role->value)->toBe('founder')
        ->and($signatures[1]->signer_role->value)->toBe('investor')
        ->and($signatures[0]->method)->toBe(SignatureMethod::External)
        ->and($signatures[0]->status)->toBe(SignatureStatus::Requested)
        // Relance idempotente : pas de doublon de demande.
        ->and(app(RequestContractSignatures::class)->handle($founder, $workspace, $document))->toHaveCount(2);

    app(RecordSignatureDeposit::class)->handle($founder, $workspace, $signatures[0], [
        'signature_evidence' => ['file' => 'bulletin-fondateur.pdf', 'sha256' => 'abc'],
    ]);

    $signed = ContractSignature::query()->whereKey($signatures[0]->id)->first();
    expect($signed->status)->toBe(SignatureStatus::Completed)
        ->and($signed->signature_evidence['file'])->toBe('bulletin-fondateur.pdf')
        // Un document à une signature manquante reste pending_signature.
        ->and($document->fresh()->status)->toBe(ContractDocumentStatus::PendingSignature);

    app(RecordSignatureDeposit::class)->handle($workspace->dealRoom->investor, $workspace, $signatures[1], [
        'signature_evidence' => ['file' => 'bulletin-investisseur.pdf'],
    ]);

    $final = $document->fresh();
    expect($final->status)->toBe(ContractDocumentStatus::Signed)
        ->and($final->signed_deposited_at)->not->toBeNull();
});

it('closes the operation only after every gate, then freezes participants', function () {
    $workspace = cformWorkspace();
    $founder = $workspace->dealRoom->entrepreneur;

    // Porte 1 : contrôle critique non résolu.
    FinancialConsistencyCheck::forceCreate([
        'investment_contract_workspace_id' => $workspace->id,
        'batch' => 'seed-batch',
        'type' => FinancialCheckType::DoubleCounting->value,
        'status' => CheckStatus::Failed->value,
        'severity' => CheckSeverity::Critical->value,
        'title' => 'Comptage double dette/capital',
        'message' => 'Comptage double dette/capital.',
        'details' => ['seeded' => true],
    ]);

    app(CreateContractCondition::class)->handle($founder, $workspace, [
        'type' => ContractConditionType::Obligation->value,
        'description' => 'Rapport trimestriel attendu du fondateur.',
    ]);

    expect(cformRun(fn () => app(CloseOperation::class)->handle($founder, $workspace)))
        ->toContain('contrôles financiers critiques');

    // Porte 2 : conditions en attente.
    FinancialConsistencyCheck::query()->where('investment_contract_workspace_id', $workspace->id)
        ->update(['resolved_at' => now()]);

    expect(cformRun(fn () => app(CloseOperation::class)->handle($founder, $workspace)))
        ->toContain('conditions restent en attente');

    // Porte 3 : revue juridique absente.
    $conditions = $workspace->conditions()->get();
    foreach ($conditions as $condition) {
        app(ResolveContractCondition::class)->handle($founder, $workspace, $condition, ['decision' => 'fulfilled']);
    }

    expect(cformRun(fn () => app(CloseOperation::class)->handle($founder, $workspace)))
        ->toContain('revue juridique');

    // Seul users.manage conduit la revue ; l'admin (tiers) passe.
    $admin = cformAdmin();
    expect(cformRun(fn () => app(LegalReviewOperation::class)->handle($founder, $workspace)))
        ->toContain('gestion juridique');

    app(LegalReviewOperation::class)->handle($admin, $workspace);
    $reviewed = $workspace->fresh();
    expect($reviewed->legal_reviewed_at)->not->toBeNull()
        ->and($reviewed->legalVerifiedBy->id)->toBe($admin->id);

    $closed = app(CloseOperation::class)->handle($founder, $workspace);
    expect($closed->status)->toBe(WorkspaceStatus::Closed)
        ->and($closed->closed_at)->not->toBeNull()
        ->and(AuditLog::query()->where('action', 'workspace.closed')->count())->toBe(1);

    // Tout est figé après clôture : structure, document, condition.
    expect(cformRun(fn () => cformDocument($workspace->fresh())))
        ->toContain('clôturée ou annulée')
        ->and(cformRun(fn () => app(CreateContractCondition::class)->handle($founder, $workspace->fresh(), [
            'type' => ContractConditionType::Covenant->value,
            'description' => 'Ne peut plus être créée après clôture.',
        ])))->toContain('clôturée ou annulée');
});

it('cancels the operation and lets participants know it is terminal', function () {
    $workspace = cformWorkspace();
    $investor = $workspace->dealRoom->investor;

    app(CancelOperation::class)->handle($investor, $workspace);

    $cancelled = $workspace->fresh();
    expect($cancelled->status)->toBe(WorkspaceStatus::Cancelled)
        ->and(AuditLog::query()->where('action', 'workspace.cancelled')->count())->toBe(1);

    expect(cformRun(fn () => app(CancelOperation::class)->handle($investor, $cancelled)))
        ->toContain('déjà clôturée ou annulée')
        ->and(cformRun(fn () => app(CloseOperation::class)->handle($investor, $cancelled)))
        ->toContain('déjà clôturée ou annulée');
});
