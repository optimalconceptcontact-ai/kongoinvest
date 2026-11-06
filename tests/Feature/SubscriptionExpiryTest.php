<?php

use App\Enums\SubscriptionStatus;
use App\Listeners\SyncPlanSubscriptionFromCashier;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\SubscriptionService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SubscriptionPlansSeeder;
use Laravel\Cashier\Events\WebhookHandled;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(SubscriptionPlansSeeder::class);
});

function paidSubscriptionFor(User $user): User
{
    $service = app(SubscriptionService::class);
    $service->assignFreePlan($user);
    $service->switchPlan($user, SubscriptionPlan::where('key', 'entrepreneur')->firstOrFail());

    return $user->fresh();
}

function notificationCount(User $user, string $type): int
{
    return $user->notifications()->where('data->type', $type)->count();
}

function cardSubscriptionFor(User $user, string $customerId = 'cus_test_card', string $subscriptionId = 'sub_test_card'): void
{
    $user->forceFill(['stripe_id' => $customerId])->save();

    app(SubscriptionService::class)->syncPlanFromCashierSubscription(
        $user,
        SubscriptionPlan::where('key', 'entrepreneur')->firstOrFail(),
        $subscriptionId,
        'monthly',
    );
}

function stripeWebhook(string $type, array $object): void
{
    app(SyncPlanSubscriptionFromCashier::class)->handle(new WebhookHandled([
        'type' => $type,
        'data' => ['object' => $object],
    ]));
}

it('gives annual plans a 365-day end date', function () {
    $user = User::factory()->entrepreneur()->create();
    $service = app(SubscriptionService::class);
    $service->assignFreePlan($user);

    $sub = $service->switchPlan($user, SubscriptionPlan::where('key', 'entrepreneur')->firstOrFail(), 'annual');

    expect($sub->billing_period)->toBe('annual');
    expect($sub->ends_at?->startOfDay()->equalTo($sub->starts_at->copy()->startOfDay()->addDays(365)))->toBeTrue();
});

it('mirrors a stripe subscription deletion locally and notifies', function () {
    $user = User::factory()->entrepreneur()->create();
    $user->forceFill(['stripe_id' => 'cus_test_deleted'])->save();
    $service = app(SubscriptionService::class);
    $service->assignFreePlan($user);

    $service->syncPlanFromCashierSubscription(
        $user,
        SubscriptionPlan::where('key', 'entrepreneur')->firstOrFail(),
        'sub_test_deleted',
        'monthly',
    );

    app(SyncPlanSubscriptionFromCashier::class)->handle(new WebhookHandled([
        'type' => 'customer.subscription.deleted',
        'data' => ['object' => ['id' => 'sub_test_deleted', 'customer' => 'cus_test_deleted', 'status' => 'canceled']],
    ]));

    $row = $user->planSubscriptions()->where('provider_subscription_id', 'sub_test_deleted')->firstOrFail();
    expect($row->status)->toBe(SubscriptionStatus::Canceled);
    expect($service->currentPlanFor($user->fresh())->key)->toBe('free');
    expect(notificationCount($user->fresh(), 'subscription_canceled'))->toBe(1);
});

it('gives paid plans a 30-day end date and leaves free open-ended', function () {
    $user = paidSubscriptionFor(User::factory()->entrepreneur()->create());
    $sub = app(SubscriptionService::class)->activeSubscriptionFor($user);

    expect($sub->ends_at?->startOfDay()->equalTo($sub->starts_at->copy()->startOfDay()->addDays(30)))->toBeTrue();
    expect($sub->grace_extended_at)->toBeNull();

    app(SubscriptionService::class)->switchPlan($user, SubscriptionPlan::where('key', 'free')->firstOrFail());

    expect(app(SubscriptionService::class)->activeSubscriptionFor($user->fresh())->ends_at)->toBeNull();
});

it('warns 5 days before expiry, once even when run twice the same day', function () {
    $user = paidSubscriptionFor(User::factory()->entrepreneur()->create());

    $this->travelTo(now()->addDays(25));
    $this->artisan('subscriptions:process-expirations')->assertSuccessful();
    $this->artisan('subscriptions:process-expirations')->assertSuccessful();

    expect(notificationCount($user->fresh(), 'subscription_expiring'))->toBe(1);
    expect(app(SubscriptionService::class)->currentPlanFor($user->fresh())->key)->toBe('entrepreneur');
});

it('reminds at 3 days and 1 day left, then grants a 7-day grace period on expiry', function () {
    $user = paidSubscriptionFor(User::factory()->entrepreneur()->create());
    $service = app(SubscriptionService::class);

    $this->travelTo(now()->addDays(27));
    $this->artisan('subscriptions:process-expirations')->assertSuccessful();
    expect(notificationCount($user->fresh(), 'subscription_grace_reminder'))->toBe(1);

    $this->travelTo(now()->addDays(2)); // 1 day left
    $this->artisan('subscriptions:process-expirations')->assertSuccessful();
    expect(notificationCount($user->fresh(), 'subscription_grace_reminder'))->toBe(2);

    $this->travelTo(now()->addDays(2)); // past ends_at
    $this->artisan('subscriptions:process-expirations')->assertSuccessful();

    $sub = $service->activeSubscriptionFor($user->fresh());
    expect($sub->grace_extended_at)->not->toBeNull();
    expect($service->currentPlanFor($user->fresh())->key)->toBe('entrepreneur');
    expect(notificationCount($user->fresh(), 'subscription_grace_started'))->toBe(1);
});

it('downgrades to free one week after expiry without renewal, and keeps pushing renewal meanwhile', function () {
    $user = paidSubscriptionFor(User::factory()->entrepreneur()->create());
    $service = app(SubscriptionService::class);

    $this->travelTo(now()->addDays(31)); // past ends_at: grace starts
    $this->artisan('subscriptions:process-expirations')->assertSuccessful();
    expect($service->currentPlanFor($user->fresh())->key)->toBe('entrepreneur');

    $this->travelTo(now()->addDays(4)); // 3 days of grace left
    $this->artisan('subscriptions:process-expirations')->assertSuccessful();
    expect(notificationCount($user->fresh(), 'subscription_grace_reminder'))->toBe(1);

    $this->travelTo(now()->addDays(4)); // grace over
    $this->artisan('subscriptions:process-expirations')->assertSuccessful();

    expect($service->currentPlanFor($user->fresh())->key)->toBe('free');
    expect(notificationCount($user->fresh(), 'subscription_downgraded'))->toBe(1);
});

it('never touches a subscription the user already renewed', function () {
    $user = paidSubscriptionFor(User::factory()->entrepreneur()->create());
    $service = app(SubscriptionService::class);
    $oldId = $service->activeSubscriptionFor($user)->id;

    $this->travelTo(now()->addDays(31)); // old row expires, grace starts
    $this->artisan('subscriptions:process-expirations')->assertSuccessful();

    // Renewal during grace (same paid plan repaid): the end date is pushed
    // out a fresh 30 days on the same row and the consumed grace clears.
    $service->switchPlan($user->fresh(), SubscriptionPlan::where('key', 'entrepreneur')->firstOrFail());

    $renewed = $service->activeSubscriptionFor($user->fresh());
    expect($renewed->id)->toBe($oldId);
    expect($renewed->grace_extended_at)->toBeNull();

    $this->travelTo(now()->addDays(10)); // past the old grace end
    $this->artisan('subscriptions:process-expirations')->assertSuccessful();

    $current = $service->activeSubscriptionFor($user->fresh());
    expect($current->id)->toBe($oldId);
    expect($service->currentPlanFor($user->fresh())->key)->toBe('entrepreneur');
    expect($current->status)->toBe(SubscriptionStatus::Active);
    expect(notificationCount($user->fresh(), 'subscription_downgraded'))->toBe(0);
});

it('flags a stripe subscription past due, notifies once, and gates to free', function () {
    $user = User::factory()->entrepreneur()->create();
    cardSubscriptionFor($user);

    $pastDue = ['id' => 'sub_test_card', 'customer' => 'cus_test_card', 'status' => 'past_due'];
    stripeWebhook('customer.subscription.updated', $pastDue);
    stripeWebhook('customer.subscription.updated', $pastDue); // repeat: stays silent

    $row = $user->planSubscriptions()->where('provider_subscription_id', 'sub_test_card')->firstOrFail();
    expect($row->status)->toBe(SubscriptionStatus::PastDue);
    expect(notificationCount($user->fresh(), 'subscription_past_due'))->toBe(1);
    expect(app(SubscriptionService::class)->currentPlanFor($user->fresh())->key)->toBe('free');
});

it('recovers a past due subscription in place without stacking rows', function () {
    $user = User::factory()->entrepreneur()->create();
    cardSubscriptionFor($user);
    SubscriptionPlan::where('key', 'entrepreneur')->firstOrFail()
        ->forceFill(['stripe_price_id' => 'price_test_recovery'])->save();

    stripeWebhook('customer.subscription.updated', ['id' => 'sub_test_card', 'customer' => 'cus_test_card', 'status' => 'past_due']);
    expect($user->planSubscriptions()->where('provider_subscription_id', 'sub_test_card')->firstOrFail()->status)
        ->toBe(SubscriptionStatus::PastDue);

    stripeWebhook('customer.subscription.updated', [
        'id' => 'sub_test_card',
        'customer' => 'cus_test_card',
        'status' => 'active',
        'items' => ['data' => [['price' => ['id' => 'price_test_recovery']]]],
    ]);

    $rows = $user->planSubscriptions()->where('provider_subscription_id', 'sub_test_card')->get();
    expect($rows)->toHaveCount(1);
    expect($rows->first()->status)->toBe(SubscriptionStatus::Active);
    expect(app(SubscriptionService::class)->currentPlanFor($user->fresh())->key)->toBe('entrepreneur');
});

it('treats a stripe updated-to-canceled like a deletion', function () {
    $user = User::factory()->entrepreneur()->create();
    cardSubscriptionFor($user);

    stripeWebhook('customer.subscription.updated', ['id' => 'sub_test_card', 'customer' => 'cus_test_card', 'status' => 'canceled']);

    $row = $user->planSubscriptions()->where('provider_subscription_id', 'sub_test_card')->firstOrFail();
    expect($row->status)->toBe(SubscriptionStatus::Canceled);
    expect(app(SubscriptionService::class)->currentPlanFor($user->fresh())->key)->toBe('free');
    expect(notificationCount($user->fresh(), 'subscription_canceled'))->toBe(1);
});
