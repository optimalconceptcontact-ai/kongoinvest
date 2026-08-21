<?php

namespace App\Services;

use App\Enums\SubscriptionFeature;
use App\Enums\SubscriptionPlanKey;
use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubscriptionService
{
    /**
     * A user always has an "effective" plan even if the `subscriptions`
     * table has no row for them yet (shouldn't happen post-registration,
     * but this keeps every caller safe regardless) — falls back to Free.
     */
    public function currentPlanFor(User $user): SubscriptionPlan
    {
        return $this->activeSubscriptionFor($user)?->plan
            ?? SubscriptionPlan::where('key', SubscriptionPlanKey::Free->value)->firstOrFail();
    }

    public function activeSubscriptionFor(User $user): ?Subscription
    {
        return $user->subscriptions()
            ->whereIn('status', [SubscriptionStatus::Active->value, SubscriptionStatus::Trialing->value])
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->latest('starts_at')
            ->first();
    }

    public function hasFeature(User $user, SubscriptionFeature $feature): bool
    {
        return $this->currentPlanFor($user)->hasFeature($feature);
    }

    public function limit(User $user, string $key): ?int
    {
        return $this->currentPlanFor($user)->limit($key);
    }

    /**
     * Called once, at registration (spec §7 — every user gets a working
     * baseline immediately, not after a separate "choose a plan" step).
     */
    public function assignFreePlan(User $user): Subscription
    {
        $free = SubscriptionPlan::where('key', SubscriptionPlanKey::Free->value)->firstOrFail();

        return $user->subscriptions()->create([
            'subscription_plan_id' => $free->id,
            'status' => SubscriptionStatus::Active->value,
            'starts_at' => now(),
        ]);
    }

    /**
     * Self-service plan switch. No payment is actually taken yet — that
     * is Phase 10's `PaymentProviderInterface`. This method is the seam:
     * once billing exists, it gains a "charge first, then call this" step
     * without anything else in the app needing to change.
     */
    public function switchPlan(User $user, SubscriptionPlan $plan): Subscription
    {
        // isRoleAgnostic() (Free) plans skip this — anyone may hold Free.
        // Anything else must match the user's actual Spatie role, checked
        // server-side regardless of what the client requested (spec
        // §100) — an entrepreneur can never end up on the Investor plan.
        if (! $plan->isRoleAgnostic() && ! $user->hasRole($plan->role_scope->value)) {
            throw ValidationException::withMessages([
                'plan' => "Ce plan n'est pas disponible pour votre rôle.",
            ]);
        }

        return DB::transaction(function () use ($user, $plan) {
            $current = $this->activeSubscriptionFor($user);

            if ($current && $current->plan->id === $plan->id) {
                return $current; // no-op, already on this plan
            }

            $current?->update([
                'status' => SubscriptionStatus::Canceled->value,
                'ends_at' => now(),
                'canceled_at' => now(),
            ]);

            return $user->subscriptions()->create([
                'subscription_plan_id' => $plan->id,
                'status' => SubscriptionStatus::Active->value,
                'starts_at' => now(),
            ]);
        });
    }
}
