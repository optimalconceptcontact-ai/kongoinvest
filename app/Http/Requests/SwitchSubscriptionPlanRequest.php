<?php

namespace App\Http\Requests;

use App\Models\Subscription;
use Illuminate\Foundation\Http\FormRequest;

class SwitchSubscriptionPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage', Subscription::class) ?? false;
    }

    public function rules(): array
    {
        return [
            // Role-compatibility is NOT checked here — that's
            // SubscriptionService::switchPlan()'s job, since it needs the
            // resolved model and the user's actual Spatie roles, not just
            // a value in isolation (spec §100).
            'plan' => ['required', 'string', 'exists:subscription_plans,key'],
        ];
    }
}
