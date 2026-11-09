<?php

use App\Models\FinancialInstrument;
use App\Models\FinancingRequest;
use App\Models\FinancingRequestComponent;
use App\Models\Project;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $instrumentMap = [
            'equity' => 'common_shares',
            'debt' => 'term_loan',
            'convertible_note' => 'convertible_loan',
            'grant' => 'investment_grant',
            'revenue_share' => 'rbf_agreement',
            'other' => 'bootstrapping',
        ];

        $projects = Project::with('financingRequests')->get();

        foreach ($projects as $project) {
            if ($project->financingRequests()->exists()) {
                continue;
            }

            $types = $project->investment_types ?? ['equity'];
            if (empty($types)) {
                $types = ['equity'];
            }

            $totalAmount = $project->funding_target_minor ?? 10_000_000;
            $currency = $project->funding_currency ?? 'USD';

            $request = FinancingRequest::create([
                'project_id' => $project->id,
                'company_id' => $project->company_id ?? null,
                'title' => 'Demande de financement - '.$project->title,
                'total_amount_minor' => $totalAmount,
                'currency' => $currency,
                'min_acceptable_minor' => $project->funding_min_minor,
                'status' => 'published',
            ]);

            $componentCount = count($types);
            $perComponentAmount = (int) floor($totalAmount / max(1, $componentCount));

            foreach ($types as $index => $typeKey) {
                $instCode = $instrumentMap[$typeKey] ?? 'common_shares';
                $instrument = FinancialInstrument::where('code', $instCode)->first();

                if ($instrument) {
                    // Give any rounding remainder to the first component
                    $amount = ($index === 0)
                        ? $totalAmount - ($perComponentAmount * ($componentCount - 1))
                        : $perComponentAmount;

                    FinancingRequestComponent::create([
                        'financing_request_id' => $request->id,
                        'financial_instrument_id' => $instrument->id,
                        'target_amount_minor' => $amount,
                        'min_amount_minor' => (int) round($amount * 0.5),
                        'is_mandatory' => true,
                        'sort_order' => $index + 1,
                        'status' => 'seeking',
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        // Non-destructive down method
    }
};
