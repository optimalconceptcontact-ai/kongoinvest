<?php

namespace App\Services;

use App\Enums\SubscriptionFeature;
use App\Enums\SubscriptionPlanKey;
use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubscriptionService
{
    /** Paid plans last 30 days (Free stays open-ended) — renewed by a new paid row. */
    public const PAID_PLAN_DAYS = 30;

    /** Annual billing runs a full year. */
    public const ANNUAL_PLAN_DAYS = 365;

    /** Grace period after a paid subscription expires before falling back to Free. */
    public const GRACE_DAYS = 7;

    /** Days before expiry to send the "expiring soon" warning. */
    public const EXPIRY_WARNING_DAYS = 5;

    public static function paidPlanDays(string $billingPeriod): int
    {
        return $billingPeriod === 'annual' ? self::ANNUAL_PLAN_DAYS : self::PAID_PLAN_DAYS;
    }

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
        return $user->planSubscriptions()
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

        return $user->planSubscriptions()->create([
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
    public function switchPlan(User $user, SubscriptionPlan $plan, string $billingPeriod = 'monthly'): Subscription
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

        return DB::transaction(function () use ($user, $plan, $billingPeriod) {
            $current = $this->activeSubscriptionFor($user);

            if ($current && $current->plan->id === $plan->id) {
                // Same-plan switch is a renewal, not a no-op for paid
                // plans: repaying (e.g. during the grace week) pushes the
                // end date out a fresh period — 30 days monthly, 365 annual —
                // and clears a consumed grace period. Otherwise the expiry
                // engine would downgrade a freshly-paid subscription. Free
                // stays a pure no-op.
                if ($plan->price_minor > 0) {
                    $current->forceFill([
                        'billing_period' => $billingPeriod,
                        'ends_at' => now()->addDays(self::paidPlanDays($billingPeriod)),
                        'grace_extended_at' => null,
                    ])->save();

                    return $current->fresh();
                }

                return $current;
            }

            $current?->update([
                'status' => SubscriptionStatus::Canceled->value,
                'ends_at' => now(),
                'canceled_at' => now(),
            ]);

            return $user->planSubscriptions()->create([
                'subscription_plan_id' => $plan->id,
                'billing_period' => $billingPeriod,
                'status' => SubscriptionStatus::Active->value,
                'starts_at' => now(),
                // Paid plans run 30 days monthly, 365 annual — then the
                // expiry command takes over (warning → 7-day grace → back
                // to Free). Free rows stay open-ended (ends_at null).
                'ends_at' => $plan->price_minor > 0 ? now()->addDays(self::paidPlanDays($billingPeriod)) : null,
            ]);
        });
    }

    /**
     * Called by SyncPlanSubscriptionFromCashier (webhook-driven) and by
     * BillingController::subscribe() right after a Cashier subscription is
     * created — not by user-facing request code, which is why this skips
     * switchPlan()'s hasRole() check (there's no request-scoped user role
     * to check inside a webhook). Reuses the same cancel-then-create
     * pattern so the single-active-row invariant every caller of
     * activeSubscriptionFor() relies on still holds.
     *
     * $trialEndsAt mirrors Cashier's own trial state (30-day trial on
     * paid-plan card subscriptions, still requiring a saved payment
     * method up front — see BillingController::subscribe()) so
     * SubscriptionService's own gating shows "Période d'essai" and the
     * right end date without knowing anything about Stripe itself.
     */
    public function syncPlanFromCashierSubscription(User $user, SubscriptionPlan $plan, string $stripeSubscriptionId, string $billingPeriod = 'monthly', ?Carbon $trialEndsAt = null): Subscription
    {
        return DB::transaction(function () use ($user, $plan, $stripeSubscriptionId, $billingPeriod, $trialEndsAt) {
            $status = $trialEndsAt?->isFuture() ? SubscriptionStatus::Trialing : SubscriptionStatus::Active;

            // A row already mirroring this Stripe subscription (e.g. flagged
            // PastDue during dunning) is updated in place on recovery —
            // never stacked with a duplicate row.
            $mirrored = $user->planSubscriptions()->where('provider_subscription_id', $stripeSubscriptionId)->first();

            if ($mirrored) {
                $mirrored->forceFill([
                    'subscription_plan_id' => $plan->id,
                    'billing_period' => $billingPeriod,
                    'status' => $status->value,
                    'trial_ends_at' => $trialEndsAt,
                ])->save();

                return $mirrored->fresh();
            }

            $current = $this->activeSubscriptionFor($user);

            if ($current && $current->plan->id === $plan->id) {
                // Same plan already on record — still sync status/trial_ends_at
                // instead of no-op'ing, so a trial ending (trialing -> active)
                // is reflected here instead of leaving a stale "Période
                // d'essai" badge once Stripe starts actually billing.
                $current->update(['status' => $status->value, 'trial_ends_at' => $trialEndsAt]);

                return $current->fresh();
            }

            $current?->update([
                'status' => SubscriptionStatus::Canceled->value,
                'ends_at' => now(),
                'canceled_at' => now(),
            ]);

            return $user->planSubscriptions()->create([
                'subscription_plan_id' => $plan->id,
                'billing_period' => $billingPeriod,
                'status' => $status->value,
                'starts_at' => now(),
                'trial_ends_at' => $trialEndsAt,
                'payment_provider' => 'stripe',
                'provider_subscription_id' => $stripeSubscriptionId,
            ]);
        });
    }
}
