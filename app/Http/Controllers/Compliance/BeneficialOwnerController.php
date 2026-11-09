<?php

namespace App\Http\Controllers\Compliance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Compliance\BeneficialOwnerRequest;
use App\Models\BusinessVerification;
use App\Models\ComplianceAuditLog;
use App\Services\Compliance\BeneficialOwnershipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BeneficialOwnerController extends Controller
{
    public function index(
        BusinessVerification $verification,
        BeneficialOwnershipService $uboService
    ): Response {
        $this->authorize('view', $verification);

        $ubos = $verification->beneficialOwners()->get()->map(fn ($u) => [
            'id' => $u->id,
            'fullName' => $u->fullName(),
            'nationality' => $u->nationality,
            'residence' => $u->country_of_residence,
            'directPercentage' => round($u->direct_ownership_bps / 100, 2),
            'indirectPercentage' => round($u->indirect_ownership_bps / 100, 2),
            'totalPercentage' => $u->totalEffectivePercentage(),
            'votingPercentage' => round($u->voting_rights_bps / 100, 2),
            'controlNature' => $u->control_nature->value,
            'controlNatureLabel' => $u->control_nature->label(),
            'isPep' => $u->is_pep,
            'pepDetails' => $u->pep_details,
            'meetsThreshold' => $u->meets_statutory_threshold,
            'isVerified' => $u->is_verified,
        ]);

        $graph = $uboService->buildOwnershipGraph($verification);

        return Inertia::render('Compliance/BeneficialOwners', [
            'verification' => [
                'id' => $verification->id,
                'companyName' => $verification->company_name,
                'status' => $verification->status,
            ],
            'beneficialOwners' => $ubos,
            'ownershipGraph' => $graph,
        ]);
    }

    public function store(
        BeneficialOwnerRequest $request,
        BusinessVerification $verification,
        BeneficialOwnershipService $uboService
    ): RedirectResponse {
        $this->authorize('update', $verification);

        $data = $request->validated();

        if ($request->hasFile('identity_document_file')) {
            $data['identity_document_file_path'] = $request->file('identity_document_file')->store('ubo-documents', 'private');
        }

        $ubo = $uboService->declareBeneficialOwner($verification, $data);

        $this->auditUboChange($request, $verification, 'ubo.declared', [
            'beneficial_owner_id' => $ubo->id,
            'full_name' => $ubo->fullName(),
            'total_bps' => $ubo->direct_ownership_bps + $ubo->indirect_ownership_bps,
            'is_pep' => (bool) $ubo->is_pep,
        ]);

        return back()->with('success', 'Bénéficiaire effectif enregistré avec succès.');
    }

    public function storeRelationship(
        Request $request,
        BusinessVerification $verification,
        BeneficialOwnershipService $uboService
    ): RedirectResponse {
        $this->authorize('update', $verification);

        $data = $request->validate([
            'parent_entity_type' => ['required', 'string', 'in:individual,legal_entity'],
            'parent_name' => ['required', 'string', 'max:200'],
            'parent_registration_number' => ['nullable', 'string', 'max:100'],
            'parent_country' => ['required', 'string', 'size:2'],
            'child_entity_name' => ['required', 'string', 'max:200'],
            'child_registration_number' => ['nullable', 'string', 'max:100'],
            'tier_level' => ['required', 'integer', 'min:1', 'max:5'],
            'equity_percentage_bps' => ['required', 'integer', 'min:0', 'max:10000'],
            'voting_percentage_bps' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'control_mechanism' => ['required', 'string'],
        ]);

        $uboService->recordOwnershipLink($verification, $data);

        $this->auditUboChange($request, $verification, 'ubo.relationship_recorded', [
            'parent_name' => $data['parent_name'],
            'child_entity_name' => $data['child_entity_name'],
            'tier_level' => $data['tier_level'],
            'equity_percentage_bps' => $data['equity_percentage_bps'],
        ]);

        return back()->with('success', 'Lien de détention ajouté à l\'organigramme.');
    }

    public function graphData(
        BusinessVerification $verification,
        BeneficialOwnershipService $uboService
    ): JsonResponse {
        $this->authorize('view', $verification);

        return response()->json($uboService->buildOwnershipGraph($verification));
    }

    private function auditUboChange(
        Request $request,
        BusinessVerification $verification,
        string $event,
        array $context
    ): void {
        ComplianceAuditLog::create([
            'compliance_case_id' => $verification->compliance_case_id,
            'actor_user_id' => $request->user()?->id,
            'event' => $event,
            'subject_type' => $verification::class,
            'subject_id' => $verification->id,
            'context' => $context,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'legal_retention_required' => true,
            'retention_until' => now()->addYears(config('compliance.legal.retention_years', 10)),
        ]);
    }
}
