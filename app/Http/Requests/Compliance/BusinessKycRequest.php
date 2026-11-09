<?php

namespace App\Http\Requests\Compliance;

use App\Enums\LegalForm;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BusinessKycRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:200'],
            'legal_form' => ['required', Rule::enum(LegalForm::class)],
            'rccm_number' => ['required', 'string', 'max:100'],
            'tax_identification_number' => ['required', 'string', 'max:100'],
            'national_identification_number' => ['nullable', 'string', 'max:100'],
            'registered_office_address' => ['required', 'string', 'max:255'],
            'country_of_incorporation' => ['required', 'string', 'size:2'],
            'date_of_incorporation' => ['required', 'date'],
            'corporate_purpose' => ['required', 'string', 'max:1000'],
            'share_capital_minor' => ['required', 'integer', 'min:1'],
            'share_capital_currency' => ['required', 'string', 'size:3'],
            'main_economic_activity' => ['required', 'string', 'max:150'],
            'industry_sector' => ['required', 'string', 'max:100'],
            'legal_representative_name' => ['required', 'string', 'max:150'],
            'legal_representative_title' => ['required', 'string', 'max:100'],
            'has_sectorial_authorization' => ['boolean'],

            // Core mandatory documents
            'rccm_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'statutes_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'nif_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }
}
