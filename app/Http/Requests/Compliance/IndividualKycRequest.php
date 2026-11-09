<?php

namespace App\Http\Requests\Compliance;

use App\Enums\DocumentType;
use App\Rules\DrcVoterCardNumberRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndividualKycRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $docType = $this->input('document_type');

        $docNumberRules = ['required', 'string', 'max:50'];
        if ($docType === DocumentType::DrcVoterCard->value) {
            $docNumberRules[] = new DrcVoterCardNumberRule;
        }

        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'postnom' => ['nullable', 'string', 'max:100'],
            'date_of_birth' => ['required', 'date', 'before:today -18 years'],
            'place_of_birth' => ['required', 'string', 'max:150'],
            'nationalities' => ['required', 'array', 'min:1'],
            'nationalities.*' => ['string', 'size:2'],
            'residential_address' => ['required', 'string', 'max:255'],
            'residential_country' => ['required', 'string', 'size:2'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:150'],
            'profession' => ['required', 'string', 'max:150'],
            'employer_or_activity' => ['nullable', 'string', 'max:150'],

            'is_pep' => ['required', 'boolean'],
            'pep_function' => ['nullable', 'required_if:is_pep,true', 'string', 'max:255'],
            'pep_start_date' => ['nullable', 'date'],
            'pep_end_date' => ['nullable', 'date'],

            'is_acting_for_third_party' => ['required', 'boolean'],
            'third_party_details' => ['nullable', 'required_if:is_acting_for_third_party,true', 'string', 'max:500'],

            // Identification Document
            'document_type' => ['required', Rule::enum(DocumentType::class)],
            'issuing_country' => ['required', 'string', 'size:2'],
            'document_number' => $docNumberRules,
            'issued_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'front_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'back_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],

            // Selfie / Biometric check
            'selfie_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:10240'],
        ];
    }
}
