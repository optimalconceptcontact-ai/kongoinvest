<?php

namespace App\Http\Controllers\Compliance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Compliance\SourceOfFundsRequest;
use App\Models\ComplianceAuditLog;
use App\Models\SourceOfFundsDeclaration;
use App\Models\SourceOfWealthDeclaration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SourceOfFundsController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $sofList = $user->sourceOfFundsDeclarations()->latest()->get()->map(fn ($s) => [
            'id' => $s->id,
            'amountFormatted' => $s->amountFormatted(),
            'category' => $s->funds_category->value,
            'categoryLabel' => $s->funds_category->label(),
            'origin' => $s->origin_description,
            'bank' => $s->bank_name,
            'isVerified' => $s->is_verified,
            'createdAt' => $s->created_at->format('Y-m-d'),
        ]);

        $sow = SourceOfWealthDeclaration::where('user_id', $user->id)->latest()->first();

        return Inertia::render('Compliance/SourceOfFunds', [
            'declarations' => $sofList,
            'sourceOfWealth' => $sow ? [
                'id' => $sow->id,
                'description' => $sow->wealth_description,
                'components' => $sow->wealth_components,
                'isVerified' => $sow->is_verified,
            ] : null,
        ]);
    }

    public function store(SourceOfFundsRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $paths = [];
        if ($request->hasFile('supporting_documents')) {
            foreach ($request->file('supporting_documents') as $file) {
                $paths[] = $file->store('sof-documents', 'private');
            }
        }

        $latestCase = $user->complianceCases()->latest()->first();

        $declaration = SourceOfFundsDeclaration::create([
            'compliance_case_id' => $latestCase?->id,
            'user_id' => $user->id,
            'operation_reference' => $data['operation_reference'] ?? null,
            'amount_minor' => $data['amount_minor'],
            'currency' => $data['currency'],
            'funds_category' => $data['funds_category'],
            'origin_description' => $data['origin_description'],
            'bank_name' => $data['bank_name'] ?? null,
            'bank_country' => $data['bank_country'] ?? 'CD',
            'account_iban_or_number_encrypted' => $data['account_iban_or_number'] ?? null,
            'supporting_document_paths' => $paths,
        ]);

        ComplianceAuditLog::create([
            'compliance_case_id' => $latestCase?->id,
            'actor_user_id' => $user->id,
            'event' => 'sof.declaration_created',
            'subject_type' => SourceOfFundsDeclaration::class,
            'subject_id' => $declaration->id,
            'context' => [
                'amount_minor' => $data['amount_minor'],
                'currency' => $data['currency'],
                'funds_category' => $data['funds_category'],
                'supporting_documents_count' => count($paths),
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'legal_retention_required' => true,
            'retention_until' => now()->addYears(config('compliance.legal.retention_years', 10)),
        ]);

        return back()->with('success', 'Déclaration d\'origine des fonds (SOF) enregistrée.');
    }

    public function storeWealth(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'wealth_description' => ['required', 'string', 'max:1500'],
            'estimated_total_net_worth_minor' => ['nullable', 'integer', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'wealth_components' => ['nullable', 'array'],
        ]);

        $latestCase = $user->complianceCases()->latest()->first();

        SourceOfWealthDeclaration::create([
            'compliance_case_id' => $latestCase?->id,
            'user_id' => $user->id,
            'wealth_description' => $data['wealth_description'],
            'estimated_total_net_worth_minor' => $data['estimated_total_net_worth_minor'] ?? null,
            'currency' => $data['currency'],
            'wealth_components' => $data['wealth_components'] ?? [],
        ]);

        ComplianceAuditLog::create([
            'compliance_case_id' => $latestCase?->id,
            'actor_user_id' => $user->id,
            'event' => 'sow.declaration_created',
            'subject_type' => SourceOfWealthDeclaration::class,
            'subject_id' => $user->id,
            'context' => [
                'estimated_total_net_worth_minor' => $data['estimated_total_net_worth_minor'] ?? null,
                'currency' => $data['currency'],
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'legal_retention_required' => true,
            'retention_until' => now()->addYears(config('compliance.legal.retention_years', 10)),
        ]);

        return back()->with('success', 'Déclaration de patrimoine global (SOW) enregistrée.');
    }
}
