<?php

namespace App\Console\Commands;

use App\Enums\SubscriptionPlanKey;
use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Notifications\SubscriptionNotification;
use App\Services\SubscriptionService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('subscriptions:process-expirations')]
#[Description('Warn about expiring paid subscriptions, grant a 7-day grace period on expiry, then fall back to Free.')]
class ProcessSubscriptionExpirations extends Command
{
    /**
     * Paid-plan lifecycle, run daily (see routes/console.php):
     * - 30-day monthly plans, 365-day annual plans (Free stays open-ended);
     * - 5 days before expiry: "expiring soon" warning;
     * - 3 and 1 day(s) before expiry: renewal reminders (these keep
     *   firing during the grace week, pushing toward renewal);
     * - on expiry: one-time 7-day grace extension + notice;
     * - 7 days later without renewal: back to Free + notice.
     *
     * Matching is exact-day based, so a normal daily run notifies once.
     * Stripe-managed rows (provider_subscription_id set — trials and
     * auto-renewing card subscriptions) are left to Cashier/webhooks.
     */
    public function handle(SubscriptionService $subscriptions): int
    {
        $today = now()->startOfDay();
        $warned = $extended = $downgraded = 0;

        $this->backfillMissingEndsAt();

        $subs = Subscription::query()
            ->with(['user', 'plan'])
            ->whereIn('status', [SubscriptionStatus::Active->value, SubscriptionStatus::Trialing->value])
            ->whereNotNull('ends_at')
            ->whereNull('provider_subscription_id')
            ->whereHas('plan', fn ($query) => $query->where('price_minor', '>', 0))
            ->get();

        foreach ($subs as $sub) {
            $user = $sub->user;

            if (! $user) {
                continue;
            }

            // Only a row nothing superseded still counts — a renewal (or
            // any switch) cancels the old row and starts a newer active
            // one, which must then be ignored. Note this intentionally does
            // NOT use activeSubscriptionFor(): an expired row up for grace
            // or downgrade is no longer "active", yet it is still the row
            // to act on when nothing replaced it.
            $superseded = Subscription::query()
                ->where('user_id', $user->id)
                ->where('id', '>', $sub->id)
                ->whereIn('status', [SubscriptionStatus::Active->value, SubscriptionStatus::Trialing->value])
                ->exists();

            if ($superseded) {
                continue;
            }

            if ($sub->ends_at->lte(now())) {
                if ($sub->grace_extended_at === null) {
                    $sub->forceFill([
                        'ends_at' => now()->addDays(SubscriptionService::GRACE_DAYS),
                        'grace_extended_at' => now(),
                    ])->save();

                    $this->notifyOnce($user, $sub, 'subscription_grace_started', 'grace_started');
                    $extended++;
                } else {
                    $free = SubscriptionPlan::where('key', SubscriptionPlanKey::Free->value)->firstOrFail();
                    $subscriptions->switchPlan($user, $free);

                    $this->notifyOnce($user, $sub, 'subscription_downgraded', 'downgraded');
                    $downgraded++;
                }

                continue;
            }

            $daysLeft = (int) $today->diffInDays($sub->ends_at->copy()->startOfDay(), false);

            if ($daysLeft === SubscriptionService::EXPIRY_WARNING_DAYS && $sub->grace_extended_at === null) {
                $this->notifyOnce($user, $sub, 'subscription_expiring', 'expiring', $daysLeft);
                $warned++;
            } elseif ($daysLeft === 3 || $daysLeft === 1) {
                $this->notifyOnce($user, $sub, 'subscription_grace_reminder', 'grace_reminder', $daysLeft);
                $warned++;
            }
        }

        $this->info("Warnings: {$warned}, grace extensions: {$extended}, downgrades: {$downgraded}.");

        return self::SUCCESS;
    }

    /**
     * Paid rows created before ends_at existed get ends_at = starts_at +
     * their billing period (30 days monthly, 365 annual). Rows already
     * past that fall into the normal flow above (grace, then Free) with
     * their notifications.
     */
    private function backfillMissingEndsAt(): void
    {
        Subscription::query()
            ->whereIn('status', [SubscriptionStatus::Active->value, SubscriptionStatus::Trialing->value])
            ->whereNull('ends_at')
            ->whereNull('provider_subscription_id')
            ->whereHas('plan', fn ($query) => $query->where('price_minor', '>', 0))
            ->get()
            ->each(fn (Subscription $sub) => $sub->forceFill([
                'ends_at' => $sub->starts_at->copy()->addDays(SubscriptionService::paidPlanDays($sub->billing_period ?? 'monthly')),
            ])->save());
    }

    /**
     * Same-day guard: exact-day matching notifies once per daily run, and
     * this keeps a second run on the same day (overlapping crons, manual
     * re-runs) from duplicating the notification.
     */
    private function notifyOnce(User $user, Subscription $sub, string $type, string $event, int $daysLeft = 0): void
    {
        $alreadySent = $user->notifications()
            ->where('data->type', $type)
            ->where('data->subscription_id', $sub->id)
            ->whereDate('created_at', now()->toDateString())
            ->exists();

        if (! $alreadySent) {
            $user->notify(new SubscriptionNotification($sub, $event, $daysLeft));
        }
    }
}
