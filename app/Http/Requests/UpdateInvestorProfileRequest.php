<?php

namespace App\Http\Requests;

use App\Enums\FundingType;
use App\Enums\InvestmentHorizon;
use App\Enums\InvestorType;
use App\Enums\RiskAppetite;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInvestorProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isInvestor() ?? false;
    }

    public function rules(): array
    {
        return [
            'country_code' => ['required', 'string', 'size:2'],
            'city' => ['nullable', 'string', 'max:120'],
            'investor_type' => ['required', Rule::enum(InvestorType::class)],
            'funding_type' => ['nullable', Rule::enum(FundingType::class)],
            'investment_horizon' => ['nullable', Rule::enum(InvestmentHorizon::class)],
            'risk_appetite' => ['nullable', Rule::enum(RiskAppetite::class)],
            // Minor units (cents) — never float (spec §146).
            'ticket_min_minor' => ['nullable', 'integer', 'min:0'],
            'ticket_max_minor' => ['nullable', 'integer', 'min:0', 'gte:ticket_min_minor'],
            'ticket_currency' => ['required', 'string', 'size:3'],
            'preferred_geographies' => ['nullable', 'array'],
            'preferred_geographies.*' => ['string', 'size:2'],
            'investment_thesis' => ['nullable', 'string', 'max:2000'],
            'impact_focused' => ['boolean'],
            'company' => ['nullable', 'array'],
            'company.name' => ['required_with:company', 'string', 'max:255'],

            // Spec §17-18: an investor selects multiple sectors AND
            // stages — these pivots existed since Phase 3's migration
            // but weren't wired into this request until Phase 5 needed
            // real investor preferences to match against.
            'sectors' => ['nullable', 'array', 'max:8'],
            'sectors.*' => ['integer', 'exists:sectors,id'],
            'stages' => ['nullable', 'array', 'max:7'],
            'stages.*' => ['integer', 'exists:stages,id'],
        ];
    }
}
