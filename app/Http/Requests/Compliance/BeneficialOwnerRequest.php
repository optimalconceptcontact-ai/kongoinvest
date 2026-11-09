<?php

namespace App\Http\Requests\Compliance;

use App\Enums\UboControlNature;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BeneficialOwnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'postnom' => ['nullable', 'string', 'max:100'],
            'date_of_birth' => ['nullable', 'date'],
            'nationality' => ['required', 'string', 'size:2'],
            'country_of_residence' => ['required', 'string', 'size:2'],
            'residential_address' => ['nullable', 'string', 'max:255'],
            'is_pep' => ['required', 'boolean'],
            'pep_details' => ['nullable', 'string', 'max:255'],
            'direct_ownership_bps' => ['required', 'integer', 'min:0', 'max:10000'],
            'indirect_ownership_bps' => ['required', 'integer', 'min:0', 'max:10000'],
            'voting_rights_bps' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'control_nature' => ['required', Rule::enum(UboControlNature::class)],
            'justification_notes' => ['nullable', 'string', 'max:500'],
            'identity_document_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }

    /**
     * Direct + indirect ownership must never exceed 100 % of the company.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $total = (int) $this->input('direct_ownership_bps', 0)
                + (int) $this->input('indirect_ownership_bps', 0);

            if ($total > 10000) {
                $validator->errors()->add(
                    'direct_ownership_bps',
                    'Le total de la détention directe et indirecte ne peut pas dépasser 100 %.',
                );
            }
        });
    }
}
