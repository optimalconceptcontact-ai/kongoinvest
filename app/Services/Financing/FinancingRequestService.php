<?php

namespace App\Services\Financing;

use App\Models\FinancialInstrument;
use App\Models\FinancingRequest;
use App\Models\FinancingRequestComponent;
use App\Models\FinancingTerm;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

class FinancingRequestService
{
    /**
     * Synchronise la demande de financement détaillée et ses composantes mixtes pour un projet.
     * Maintient également la rétrocompatibilité avec la colonne `investment_types` du projet.
     */
    public function syncForProject(Project $project, array $data): FinancingRequest
    {
        return DB::transaction(function () use ($project, $data) {
            $totalAmount = $data['total_amount_minor'] ?? $project->funding_target_minor ?? 10_000_000;
            $currency = $data['currency'] ?? $project->funding_currency ?? 'USD';

            $request = FinancingRequest::updateOrCreate(
                ['project_id' => $project->id],
                [
                    'company_id' => $project->company_id ?? null,
                    'title' => $data['title'] ?? ('Demande de financement - '.$project->title),
                    'total_amount_minor' => $totalAmount,
                    'currency' => $currency,
                    'min_acceptable_minor' => $data['min_acceptable_minor'] ?? $project->funding_min_minor,
                    'equity_dilution_percentage_target' => $data['equity_dilution_percentage_target'] ?? null,
                    'preferred_channel_id' => $data['preferred_channel_id'] ?? null,
                    'status' => $data['status'] ?? 'published',
                    'notes' => $data['notes'] ?? null,
                ]
            );

            $componentsData = $data['components'] ?? [];

            if (! empty($componentsData)) {
                // Delete existing components not in incoming list
                $existingIds = collect($componentsData)->pluck('id')->filter()->all();
                if (! empty($existingIds)) {
                    $request->components()->whereNotIn('id', $existingIds)->delete();
                } else {
                    $request->components()->delete();
                }

                $legacyFundingTypes = [];

                foreach ($componentsData as $index => $c) {
                    $instrumentId = $c['financial_instrument_id'] ?? null;
                    if (! $instrumentId) {
                        continue;
                    }

                    $instrument = FinancialInstrument::find($instrumentId);
                    if (! $instrument) {
                        continue;
                    }

                    // Map to legacy funding type for project->investment_types
                    $legacyType = match ($instrument->instrument_category) {
                        'equity' => 'equity',
                        'debt' => 'debt',
                        'hybrid' => 'convertible_note',
                        'grant' => 'grant',
                        'revenue_share' => 'revenue_share',
                        default => 'other',
                    };
                    $legacyFundingTypes[] = $legacyType;

                    $component = FinancingRequestComponent::updateOrCreate(
                        [
                            'id' => $c['id'] ?? null,
                            'financing_request_id' => $request->id,
                        ],
                        [
                            'financial_instrument_id' => $instrumentId,
                            'target_amount_minor' => (int) ($c['target_amount_minor'] ?? round($totalAmount / count($componentsData))),
                            'min_amount_minor' => isset($c['min_amount_minor']) ? (int) $c['min_amount_minor'] : null,
                            'is_mandatory' => $c['is_mandatory'] ?? true,
                            'sort_order' => $index + 1,
                            'status' => $c['status'] ?? 'seeking',
                            'terms_summary' => $c['terms_summary'] ?? null,
                        ]
                    );

                    // Optional terms
                    if (! empty($c['terms'])) {
                        $termsData = $c['terms'];
                        FinancingTerm::updateOrCreate(
                            [
                                'termable_type' => FinancingRequestComponent::class,
                                'termable_id' => $component->id,
                            ],
                            [
                                'valuation_pre_money_minor' => $termsData['valuation_pre_money_minor'] ?? null,
                                'valuation_post_money_minor' => $termsData['valuation_post_money_minor'] ?? null,
                                'interest_rate_bps' => $termsData['interest_rate_bps'] ?? null,
                                'duration_months' => $termsData['duration_months'] ?? null,
                                'grace_period_months' => $termsData['grace_period_months'] ?? null,
                                'revenue_share_percentage' => $termsData['revenue_share_percentage'] ?? null,
                                'cap_minor' => $termsData['cap_minor'] ?? null,
                                'discount_rate_bps' => $termsData['discount_rate_bps'] ?? null,
                                'collateral_required' => $termsData['collateral_required'] ?? false,
                                'collateral_description' => $termsData['collateral_description'] ?? null,
                            ]
                        );
                    }
                }

                // Maintain backward compatibility on Project model
                $uniqueLegacy = array_values(array_unique($legacyFundingTypes));
                if (! empty($uniqueLegacy)) {
                    $project->investment_types = $uniqueLegacy;
                    $project->saveQuietly();
                }
            }

            return $request->load(['components.instrument.financingTypes', 'components.terms']);
        });
    }
}
