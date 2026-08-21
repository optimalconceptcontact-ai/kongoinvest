<?php

namespace App\Http\Requests;

use App\Enums\DrcProvince;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEntrepreneurProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        // An entrepreneur may only ever edit their own profile — there is
        // no route parameter here (it's always the authenticated user's
        // profile), so this is intentionally simple. Compare to
        // ProjectPolicy in Phase 3, which must check ownership of an
        // arbitrary {project}.
        return $this->user()?->isEntrepreneur() ?? false;
    }

    public function rules(): array
    {
        return [
            'job_title' => ['nullable', 'string', 'max:120'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'experience' => ['nullable', 'string', 'max:2000'],
            // Note: no `country` field at all — it is not user-editable,
            // by design (spec §15). Only province/city within the DRC.
            'province' => ['nullable', Rule::enum(DrcProvince::class)],
            'city' => ['nullable', 'string', 'max:120'],
            'years_experience' => ['nullable', 'integer', 'min:0', 'max:80'],
            'company' => ['nullable', 'array'],
            'company.name' => ['required_with:company', 'string', 'max:255'],
            'company.website' => ['nullable', 'url', 'max:255'],
        ];
    }
}
