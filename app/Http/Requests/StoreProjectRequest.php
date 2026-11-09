<?php

namespace App\Http\Requests;

use App\Enums\DrcProvince;
use App\Models\FinancialInstrument;
use App\Models\Project;
use App\Models\Stage;
use App\Rules\WithinFundingLimits;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Delegates to ProjectPolicy::create(), which itself checks role,
        // permission, AND the active-project limit (spec §20) — the
        // limit is enforced here, not just hidden behind a disabled
        // button in React (spec §100).
        return $this->user()?->can('create', Project::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $investmentTypes = $this->input('investment_types');
        $instrumentIds = $this->input('financial_instruments') ?? collect($this->input('financing_components', []))->pluck('financial_instrument_id')->filter()->all();

        if (empty($investmentTypes) && ! empty($instrumentIds)) {
            $instruments = FinancialInstrument::whereIn('id', $instrumentIds)->get();
            $derived = $instruments->map(function ($inst) {
                return match ($inst->instrument_category) {
                    'equity' => 'equity',
                    'debt' => 'debt',
                    'hybrid' => 'convertible_note',
                    'grant' => 'grant',
                    'revenue_share' => 'revenue_share',
                    default => 'other',
                };
            })->unique()->values()->all();

            $this->merge([
                'investment_types' => ! empty($derived) ? $derived : ['equity'],
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string', 'max:5000'],
            'executive_summary' => ['nullable', 'string', 'max:2000'],
            'problem' => ['nullable', 'string', 'max:3000'],
            'solution' => ['nullable', 'string', 'max:3000'],
            'business_model' => ['nullable', 'string', 'max:3000'],
            'market' => ['nullable', 'string', 'max:3000'],
            'competitive_advantage' => ['nullable', 'string', 'max:3000'],

            // Structured members, not a free-text bio — LinkedIn is the
            // only genuinely optional field per member.
            'team' => ['nullable', 'array'],
            'team.*.full_name' => ['required', 'string', 'max:150'],
            'team.*.role' => ['required', 'string', 'max:150'],
            'team.*.linkedin' => ['nullable', 'url', 'max:255'],
            'team.*.email' => ['required', 'email', 'max:255'],
            'team.*.phone' => ['required', 'string', 'max:30'],

            'traction' => ['nullable', 'string', 'max:3000'],
            'financial_information' => ['nullable', 'string', 'max:3000'],
            'impact' => ['nullable', 'string', 'max:3000'],
            'risks' => ['nullable', 'string', 'max:3000'],

            'funding_target_minor' => [
                'nullable',
                'integer',
                'min:0',
                new WithinFundingLimits($this->input('funding_currency', 'USD')),
            ],
            'funding_min_minor' => ['nullable', 'integer', 'min:0'],
            'funding_max_minor' => ['nullable', 'integer', 'min:0', 'gte:funding_min_minor'],
            'funding_currency' => ['required', 'string', 'size:3'],

            // Exactly or at least 1-6 financing types for backward compatibility,
            // while accepting the new dynamic instruments and mixed financing components.
            'investment_types' => ['nullable', 'array', 'min:1', 'max:6'],
            'investment_types.*' => ['string'],

            'financial_instruments' => ['nullable', 'array'],
            'financial_instruments.*' => ['integer', 'exists:financial_instruments,id'],
            'financing_components' => ['nullable', 'array'],
            'financing_components.*.financial_instrument_id' => ['required_with:financing_components', 'integer', 'exists:financial_instruments,id'],
            'financing_components.*.target_amount_minor' => ['nullable', 'integer', 'min:0'],
            'financing_components.*.min_amount_minor' => ['nullable', 'integer', 'min:0'],
            'financing_components.*.is_mandatory' => ['nullable', 'boolean'],
            'financing_request' => ['nullable', 'array'],

            'provinces' => ['nullable', 'array', $this->maxProvincesRule()],
            'provinces.*' => [Rule::enum(DrcProvince::class)],
            'cities' => ['nullable', 'array'],
            'cities.*' => ['string', 'max:120'],
            'website' => ['nullable', 'url', 'max:255'],
            'social_links' => ['nullable', 'array'],

            'sectors' => ['required', 'array', 'min:1', 'max:5'],
            'sectors.*' => ['integer', 'exists:sectors,id'],
            // Exactly one stage (spec update) — a project is at one
            // stage of maturity, never several at once.
            'stages' => ['required', 'array', 'size:1'],
            'stages.*' => ['integer', 'exists:stages,id'],
        ];
    }

    /**
     * Up to 3 provinces only for an Idea-stage project (no fixed base
     * yet) — every other stage is tied to exactly one, like before.
     */
    private function maxProvincesRule(): \Closure
    {
        return function (string $attribute, $value, \Closure $fail) {
            $stageId = $this->input('stages.0');
            $isIdea = $stageId && Stage::where('id', $stageId)->value('key') === 'idea';
            $max = $isIdea ? 3 : 1;

            if (count($value) > $max) {
                $fail($max === 1
                    ? 'Une seule province, sauf pour un projet au stade Idée (jusqu\'à 3).'
                    : "Sélectionnez au maximum {$max} provinces.");
            }
        };
    }
}
