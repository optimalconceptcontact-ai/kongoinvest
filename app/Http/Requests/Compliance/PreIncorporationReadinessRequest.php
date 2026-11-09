<?php

namespace App\Http\Requests\Compliance;

use App\Enums\LegalForm;
use App\Enums\ProjectMaturityStage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PreIncorporationReadinessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'maturity_stage' => ['nullable', Rule::enum(ProjectMaturityStage::class)],
            'intended_legal_form' => ['nullable', Rule::enum(LegalForm::class)],
            'intended_company_name' => ['nullable', 'string', 'max:200'],
            'intended_corporate_purpose' => ['nullable', 'string', 'max:1000'],
            'intended_registered_office' => ['nullable', 'string', 'max:255'],
            'intended_capital_minor' => ['nullable', 'integer', 'min:0'],
            'intended_capital_currency' => ['nullable', 'string', 'size:3'],

            'indicative_cofounders' => ['nullable', 'array'],
            'indicative_cofounders.*.name' => ['required_with:indicative_cofounders', 'string', 'max:100'],
            'indicative_cofounders.*.email' => ['nullable', 'email', 'max:100'],
            'indicative_cofounders.*.role' => ['nullable', 'string', 'max:100'],
            'indicative_cofounders.*.projected_share_bps' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'indicative_cofounders.*.is_lead' => ['nullable', 'boolean'],
        ];
    }
}
