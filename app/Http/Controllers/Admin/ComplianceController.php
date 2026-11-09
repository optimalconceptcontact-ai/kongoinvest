<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AmlAlertStatus;
use App\Enums\ComplianceCaseStatus;
use App\Http\Controllers\Controller;
use App\Models\AmlAlert;
use App\Models\ComplianceCase;
use App\Models\KycProfile;
use App\Services\Compliance\AmlRiskAssessmentService;
use App\Services\Compliance\AmlScreeningService;
use App\Services\Compliance\ComplianceCaseManagementService;
use App\Services\Compliance\PartnerBankComplianceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ComplianceController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('review', KycProfile::class);

        $statusFilter = $request->query('status');

        $query = ComplianceCase::query()
            ->with(['user', 'subject'])
            ->latest('updated_at');

        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }

        $cases = $query->paginate(15)->through(fn (ComplianceCase $c) => [
            'id' => $c->id,
            'caseNumber' => $c->case_number,
            'userName' => $c->user?->name ?? 'Utilisateur',
            'userEmail' => $c->user?->email,
            'subjectType' => class_basename($c->subject_type),
            'status' => $c->status->value,
            'statusLabel' => $c->status->label(),
            'riskLevel' => $c->risk_level->value,
            'riskLevelLabel' => $c->risk_level->label(),
            'riskScore' => $c->risk_score,
            'openAlertsCount' => $c->alerts()->where('status', AmlAlertStatus::Open)->count(),
            'submittedAt' => $c->submitted_at?->toIso8601String(),
            'updatedAt' => $c->updated_at->toIso8601String(),
        ]);

        $openAlertsTotal = AmlAlert::where('status', AmlAlertStatus::Open)->count();

        return Inertia::render('Admin/Compliance/Index', [
            'cases' => $cases,
            'openAlertsTotal' => $openAlertsTotal,
            'statusFilter' => $statusFilter,
        ]);
    }

    public function show(ComplianceCase $case, PartnerBankComplianceService $bankService): Response
    {
        $this->authorize('review', KycProfile::class);

        $case->load(['user', 'actions.actor', 'approvals.approver', 'alerts', 'riskAssessments']);

        $alerts = $case->alerts()->latest()->get()->map(fn (AmlAlert $a) => [
            'id' => $a->id,
            'type' => $a->alert_type->value,
            'typeLabel' => $a->alert_type->label(),
            'severity' => $a->severity->value,
            'severityLabel' => $a->severity->label(),
            'status' => $a->status->value,
            'statusLabel' => $a->status->label(),
            'title' => $a->title,
            'description' => $a->description,
            'metadata' => $a->metadata,
            'resolutionComment' => $a->resolution_comment,
            'resolvedAt' => $a->resolved_at?->toIso8601String(),
        ]);

        $latestRisk = $case->latestRiskAssessment();

        return Inertia::render('Admin/Compliance/Show', [
            'case' => [
                'id' => $case->id,
                'caseNumber' => $case->case_number,
                'userName' => $case->user?->name,
                'userEmail' => $case->user?->email,
                'subjectType' => class_basename($case->subject_type),
                'status' => $case->status->value,
                'statusLabel' => $case->status->label(),
                'riskLevel' => $case->risk_level->value,
                'riskLevelLabel' => $case->risk_level->label(),
                'riskScore' => $case->risk_score,
                'notes' => $case->notes,
                'submittedAt' => $case->submitted_at?->toIso8601String(),
                'reviewedAt' => $case->reviewed_at?->toIso8601String(),
                'isBlocked' => $case->status === ComplianceCaseStatus::Blocked,
                'suspensionOrBlockReason' => $case->suspension_or_block_reason,
            ],
            'alerts' => $alerts,
            'riskAssessment' => $latestRisk ? [
                'overallScore' => $latestRisk->overall_score,
                'clientScore' => $latestRisk->client_nature_score,
                'uboScore' => $latestRisk->ubo_complexity_score,
                'geoScore' => $latestRisk->geographic_score,
                'sectorScore' => $latestRisk->sectoral_score,
                'productScore' => $latestRisk->product_channel_score,
                'sofScore' => $latestRisk->source_of_funds_score,
                'txScore' => $latestRisk->transaction_behavior_score,
                'requiresEdd' => $latestRisk->requires_enhanced_due_diligence,
                'mitigatingFactors' => $latestRisk->mitigating_factors,
                'rationale' => $latestRisk->analyst_rationale,
            ] : null,
            'actions' => $case->actions->map(fn ($act) => [
                'id' => $act->id,
                'actor' => $act->actor?->name ?? 'Système',
                'actionType' => $act->action_type,
                'comment' => $act->comment,
                'createdAt' => $act->created_at->toIso8601String(),
            ]),
            'bankMatrix' => $bankService->getResponsibilitiesMatrix(),
        ]);
    }

    public function approve(
        Request $request,
        ComplianceCase $case,
        ComplianceCaseManagementService $caseService
    ): RedirectResponse {
        $this->authorize('review', KycProfile::class);

        $data = $request->validate([
            'rationale' => ['required', 'string', 'max:1000'],
            'conditions' => ['nullable', 'string', 'max:1000'],
        ]);

        $caseService->approveCase(
            $case,
            $request->user(),
            $data['rationale'],
            $data['conditions'] ?? null
        );

        return back()->with('success', 'Dossier de conformité approuvé.');
    }

    public function resolveAlert(
        Request $request,
        AmlAlert $alert,
        AmlScreeningService $screeningService,
        AmlRiskAssessmentService $riskService
    ): RedirectResponse {
        $this->authorize('review', KycProfile::class);

        $data = $request->validate([
            'decision' => ['required', 'string', 'in:false_positive,confirmed_risk,dismissed,escalated_for_dos'],
            'comment' => ['required', 'string', 'max:1000'],
        ]);

        $screeningService->resolveAlert(
            $alert,
            $data['decision'],
            $request->user(),
            $data['comment']
        );

        // Recalculate risk score after resolving alert
        $riskService->assessCase($alert->complianceCase);

        return back()->with('success', 'Alerte AML traitée.');
    }

    public function prepareDos(
        Request $request,
        ComplianceCase $case,
        ComplianceCaseManagementService $caseService
    ): JsonResponse {
        $this->authorize('review', KycProfile::class);

        $data = $request->validate([
            'narrative' => ['required', 'string', 'min:20', 'max:5000'],
            'alert_ids' => ['required', 'array', 'min:1'],
            'alert_ids.*' => ['integer'],
        ]);

        $dosPackage = $caseService->prepareSuspiciousActivityDossier(
            $case,
            $request->user(),
            $data['narrative'],
            $data['alert_ids']
        );

        return response()->json($dosPackage);
    }

    public function transmitToBank(
        ComplianceCase $case,
        PartnerBankComplianceService $bankService,
        Request $request
    ): RedirectResponse {
        $this->authorize('review', KycProfile::class);

        $bankService->transmitToBank($case, $request->user());

        return back()->with('success', 'Dossier transmis avec succès à la banque partenaire.');
    }
}
