<?php

namespace App\Http\Controllers;

use App\Actions\Contracts\ApproveContractDocument;
use App\Actions\Contracts\CancelOperation;
use App\Actions\Contracts\CloseOperation;
use App\Actions\Contracts\CreateContractCondition;
use App\Actions\Contracts\CreateContractDocument;
use App\Actions\Contracts\CreateInvestmentContractWorkspace;
use App\Actions\Contracts\DepositSignedContractDocument;
use App\Actions\Contracts\LegallyValidateContractDocument;
use App\Actions\Contracts\LegalReviewOperation;
use App\Actions\Contracts\ProposeClause;
use App\Actions\Contracts\RecordAnalysisConsent;
use App\Actions\Contracts\RecordClauseDecision;
use App\Actions\Contracts\RecordSignatureDeposit;
use App\Actions\Contracts\RequestContractSignatures;
use App\Actions\Contracts\ResolveContractCondition;
use App\Actions\Contracts\UpdateOperationStructure;
use App\Data\ContractWorkspaceData;
use App\Models\ContractClauseSelection;
use App\Models\ContractCondition;
use App\Models\ContractDocument;
use App\Models\ContractSignature;
use App\Models\DealRoom;
use App\Models\InvestmentContractWorkspace;
use App\Services\Ai\NegotiationAnalysisService;
use App\Services\Contracts\FinancialConsistencyService;
use App\Services\Contracts\LegalComplianceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Espace contractuel : ouverture depuis la DealRoom, consultation,
 * consentement et analyse IA (prompt §13-§16), structure de
 * l'opération + contrôles financiers + cycle des clauses (§7, §8,
 * §10), puis formalisation et clôture. Le controller n'orchestre
 * rien, il autorise, exécute, redirige — toute la logique vit dans
 * les actions et services dédiés.
 */
class InvestmentContractWorkspaceController extends Controller
{
    public function store(Request $request, DealRoom $dealRoom, CreateInvestmentContractWorkspace $action): RedirectResponse
    {
        // Le gating abonnement « negotiation_tools » vit dans l'action,
        // piloté par les données de plan — la policy ne vérifie que la
        // participation et la capacité « contracts.view » (spec §28).
        $this->authorize('create', [InvestmentContractWorkspace::class, $dealRoom]);

        $workspace = $action->handle($request->user(), $dealRoom);

        return redirect()
            ->route('contract-workspaces.show', $workspace)
            ->with('success', 'Espace contractuel ouvert : configurez la structure du capital avant de négocier.');
    }

    public function show(Request $request, InvestmentContractWorkspace $workspace): Response
    {
        $this->authorize('view', $workspace);

        return Inertia::render('Contracts/Workspace', [
            'workspace' => ContractWorkspaceData::fromModel($workspace, $request->user()),
        ]);
    }

    public function consent(Request $request, InvestmentContractWorkspace $workspace, RecordAnalysisConsent $action): RedirectResponse
    {
        $this->authorize('consent', $workspace);

        $action->handle($request->user(), $workspace);

        return back()->with('success', 'Consentement à l\'analyse IA enregistré.');
    }

    public function analyze(Request $request, InvestmentContractWorkspace $workspace, NegotiationAnalysisService $service): RedirectResponse
    {
        // La participation et l'état de l'opération sont déjà couverts
        // par la policy « update » ; le service garde lui-même les
        // consentements en dernier ressort (prompt §14).
        $this->authorize('update', $workspace);

        $service->analyze($request->user(), $workspace);

        return back()->with('success', 'Analyse IA de la négociation produite — consultez la synthèse ci-dessous.');
    }

    public function structure(Request $request, InvestmentContractWorkspace $workspace, UpdateOperationStructure $action): RedirectResponse
    {
        $this->authorize('update', $workspace);

        $action->handle($request->user(), $workspace, (array) $request->all());

        return back()->with('success', 'Structure de l\'opération enregistrée — contrôlez la table de capitalisation.');
    }

    public function financialChecks(
        InvestmentContractWorkspace $workspace,
        FinancialConsistencyService $financialService,
        LegalComplianceService $legalService,
    ): RedirectResponse {
        $this->authorize('update', $workspace);

        $financialRun = $financialService->run($workspace);
        $legalRun = $legalService->run($workspace);

        $blocked = ($financialRun->blocking || $legalRun->blocking)
            ? ' CONTRÔLE BLOQUANT : au moins une anomalie critique (financière ou juridique) empêche la clôture.'
            : ' Aucune anomalie critique bloquante.';

        return back()->with('success', 'Contrôles financiers (batch '.$financialRun->batch.') et contrôles juridiques (batch '.$legalRun->batch.') exécutés.'.$blocked);
    }

    public function proposeClause(Request $request, InvestmentContractWorkspace $workspace, ProposeClause $action): RedirectResponse
    {
        $this->authorize('update', $workspace);

        $action->handle($request->user(), $workspace, (array) $request->all());

        return back()->with('success', 'Clause proposée — en attente de la position de l\'autre partie.');
    }

    public function decideClause(
        Request $request,
        InvestmentContractWorkspace $workspace,
        ContractClauseSelection $clause,
        RecordClauseDecision $action,
    ): RedirectResponse {
        $this->authorize('update', $workspace);

        $action->handle($request->user(), $workspace, $clause, (array) $request->all());

        return back()->with('success', 'Décision enregistrée sur la clause.');
    }

    // ---- Formalisation (documents, conditions, signatures) ----

    public function createDocument(Request $request, InvestmentContractWorkspace $workspace, CreateContractDocument $action): RedirectResponse
    {
        $this->authorize('update', $workspace);

        $action->handle($request->user(), $workspace, (array) $request->all());

        return back()->with('success', 'Document déposé — version 1 enregistrée avec son empreinte.');
    }

    public function approveDocument(
        Request $request,
        InvestmentContractWorkspace $workspace,
        ContractDocument $document,
        ApproveContractDocument $action,
    ): RedirectResponse {
        $this->authorize('update', $workspace);

        $action->handle($request->user(), $workspace, $document);

        return back()->with('success', 'Approbation enregistrée sur le document.');
    }

    public function legallyValidateDocument(
        Request $request,
        InvestmentContractWorkspace $workspace,
        ContractDocument $document,
        LegallyValidateContractDocument $action,
    ): RedirectResponse {
        // La participation ne suffit pas ici : seul users.manage passe
        // — la policy « view » est le seul contrôle du controller (un
        // juriste n'est pas un participant).
        $this->authorize('view', $workspace);

        $action->handle($request->user(), $workspace, $document);

        return back()->with('success', 'Validation juridique enregistrée sur le document.');
    }

    public function depositSignedDocument(
        Request $request,
        InvestmentContractWorkspace $workspace,
        ContractDocument $document,
        DepositSignedContractDocument $action,
    ): RedirectResponse {
        $this->authorize('update', $workspace);

        $action->handle($request->user(), $workspace, $document);

        return back()->with('success', 'Exemplaire signé déposé — document marqué signé.');
    }

    public function createCondition(Request $request, InvestmentContractWorkspace $workspace, CreateContractCondition $action): RedirectResponse
    {
        $this->authorize('update', $workspace);

        $action->handle($request->user(), $workspace, (array) $request->all());

        return back()->with('success', 'Condition enregistrée — en attente de conduite.');
    }

    public function resolveCondition(
        Request $request,
        InvestmentContractWorkspace $workspace,
        ContractCondition $condition,
        ResolveContractCondition $action,
    ): RedirectResponse {
        $this->authorize('update', $workspace);

        $action->handle($request->user(), $workspace, $condition, (array) $request->all());

        return back()->with('success', 'Résolution de la condition enregistrée.');
    }

    public function requestSignatures(
        Request $request,
        InvestmentContractWorkspace $workspace,
        ContractDocument $document,
        RequestContractSignatures $action,
    ): RedirectResponse {
        $this->authorize('update', $workspace);

        $action->handle($request->user(), $workspace, $document, (array) $request->all());

        return back()->with('success', 'Demandes de signature créées pour les deux signataires.');
    }

    public function depositSignature(
        Request $request,
        InvestmentContractWorkspace $workspace,
        ContractSignature $signature,
        RecordSignatureDeposit $action,
    ): RedirectResponse {
        $this->authorize('update', $workspace);

        $action->handle($request->user(), $workspace, $signature, (array) $request->all());

        return back()->with('success', 'Preuve de signature enregistrée.');
    }

    // ---- Revue juridique, clôture et annulation ----

    public function legalReview(Request $request, InvestmentContractWorkspace $workspace, LegalReviewOperation $action): RedirectResponse
    {
        // Un juriste n'est pas un participant : seule la policy
        // « view » est contrôlée ici — l'action vérifie users.manage.
        $this->authorize('view', $workspace);

        $action->handle($request->user(), $workspace);

        return back()->with('success', 'Revue juridique de l\'opération enregistrée.');
    }

    public function close(Request $request, InvestmentContractWorkspace $workspace, CloseOperation $action): RedirectResponse
    {
        $this->authorize('update', $workspace);

        $action->handle($request->user(), $workspace);

        return back()->with('success', 'Opération clôturée — les échanges contractuels sont figés.');
    }

    public function cancel(Request $request, InvestmentContractWorkspace $workspace, CancelOperation $action): RedirectResponse
    {
        $this->authorize('update', $workspace);

        $action->handle($request->user(), $workspace);

        return back()->with('success', 'Opération annulée.');
    }
}
