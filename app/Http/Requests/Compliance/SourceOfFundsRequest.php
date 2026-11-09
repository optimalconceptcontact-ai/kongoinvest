<?php

namespace App\Http\Requests\Compliance;

use App\Enums\SourceOfFundsCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SourceOfFundsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'amount_minor' => ['required', 'integer', 'min:100'],
            'currency' => ['required', 'string', 'size:3'],
            'funds_category' => ['required', Rule::enum(SourceOfFundsCategory::class)],
            'origin_description' => ['required', 'string', 'max:1000'],
            'bank_name' => ['nullable', 'string', 'max:150'],
            'bank_country' => ['nullable', 'string', 'size:2'],
            'account_iban_or_number' => ['nullable', 'string', 'max:50'],
            'operation_reference' => ['nullable', 'string', 'max:100'],
            'supporting_documents' => ['nullable', 'array', 'min:1'],
            'supporting_documents.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }
}
