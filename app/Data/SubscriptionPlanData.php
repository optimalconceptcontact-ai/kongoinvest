<?php

namespace App\Data;

use App\Models\SubscriptionPlan;
use Spatie\LaravelData\Data;

class SubscriptionPlanData extends Data
{
    public function __construct(
        public int $id,
        public string $key,
        public string $name,
        public ?string $roleScope,
        public int $priceMinor,
        public string $currency,
        public string $billingPeriod,
        /** @var array<string, int> */
        public array $limits,
        /** @var string[] */
        public array $features,
        public bool $availableToViewer,
        public bool $isCurrent,
    ) {}

    public static function fromModel(SubscriptionPlan $plan, ?\App\Models\User $viewer, bool $isCurrent = false): self
    {
        return new self(
            id: $plan->id,
            key: $plan->key,
            name: $plan->name,
            roleScope: $plan->role_scope?->value,
            priceMinor: $plan->price_minor,
            currency: $plan->currency,
            billingPeriod: $plan->billing_period,
            limits: $plan->limits ?? [],
            features: $plan->features ?? [],
            availableToViewer: $viewer !== null && ($plan->isRoleAgnostic() || $viewer->hasRole($plan->role_scope->value)),
            isCurrent: $isCurrent,
        );
    }
}
