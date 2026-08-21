<?php

namespace App\Http\Requests;

use App\Enums\DrcProvince;
use App\Enums\FundingType;
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
        return $this->user()?->can('create', \App\Models\Project::class) ?? false;
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
            'team' => ['nullable', 'string', 'max:3000'],
            'traction' => ['nullable', 'string', 'max:3000'],
            'financial_information' => ['nullable', 'string', 'max:3000'],
            'impact' => ['nullable', 'string', 'max:3000'],
            'risks' => ['nullable', 'string', 'max:3000'],

            'funding_target_minor' => ['nullable', 'integer', 'min:0'],
            'funding_min_minor' => ['nullable', 'integer', 'min:0'],
            'funding_max_minor' => ['nullable', 'integer', 'min:0', 'gte:funding_min_minor'],
            'funding_currency' => ['required', 'string', 'size:3'],

            // Exactly 3 financing types the entrepreneur is open to —
            // the investor later picks one of these three when
            // requesting AI contract generation in the Deal Room.
            'investment_types' => ['required', 'array', 'size:3'],
            'investment_types.*' => [Rule::enum(FundingType::class)],

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
            $isIdea = $stageId && \App\Models\Stage::where('id', $stageId)->value('key') === 'idea';
            $max = $isIdea ? 3 : 1;

            if (count($value) > $max) {
                $fail($max === 1
                    ? 'Une seule province, sauf pour un projet au stade Idée (jusqu\'à 3).'
                    : "Sélectionnez au maximum {$max} provinces.");
            }
        };
    }
}
