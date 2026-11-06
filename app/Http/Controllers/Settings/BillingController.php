<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Payments\CreatePayment;
use App\Enums\PaymentProviderName;
use App\Enums\PaymentPurpose;
use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Cashier\Exceptions\IncompletePayment;

class BillingController extends Controller
{
    public function checkout(Request $request, SubscriptionPlan $plan): Response
    {
        abort_if($plan->price_minor === 0, 404); // Free never goes through checkout

        if (! $plan->isRoleAgnostic() && ! $request->user()->hasRole($plan->role_scope->value)) {
            abort(403, "Ce plan n'est pas disponible pour votre rôle.");
        }

        return Inertia::render('Settings/Billing/Checkout', [
            'plan' => $plan->only(['id', 'key', 'name', 'price_minor', 'annual_price_minor', 'currency']),
        ]);
    }

    /**
     * Card rail — goes through Cashier now. Returns a SetupIntent client
     * secret, not a PaymentIntent: a subscription needs a reusable saved
     * payment method, not a one-off charge. The frontend mounts Stripe
     * Elements against it and collects card details itself through
     * Stripe's own hosted iframe fields — raw card data never touches
     * this endpoint (PCI scope stays with Stripe).
     */
    public function intent(Request $request, SubscriptionPlan $plan): JsonResponse
    {
        abort_if($plan->price_minor === 0, 404);

        if (! $plan->isRoleAgnostic() && ! $request->user()->hasRole($plan->role_scope->value)) {
            abort(403);
        }

        try {
            $setupIntent = $request->user()->createSetupIntent();
        } catch (\Exception $e) {
            return response()->json(['message' => "Le paiement par carte n'est pas disponible pour le moment."], 503);
        }

        return response()->json(['clientSecret' => $setupIntent->client_secret]);
    }

    /**
     * Every paid plan gets a 30-day trial — the user still goes through
     * this exact same checkout (SetupIntent, saved card) rather than a
     * separate no-payment-info "start trial" flow; Stripe just doesn't
     * charge the saved card until the trial ends.
     */
    private const TRIAL_DAYS = 30;

    /**
     * Creates the actual Cashier subscription once the SetupIntent above
     * confirmed and yielded a reusable payment method. Mirrors the result
     * into the app's own plan_subscriptions table immediately (rather than
     * waiting for the customer.subscription.created webhook round-trip)
     * so the user isn't stuck seeing their old plan for a few seconds —
     * SyncPlanSubscriptionFromCashier keeps it correct after this for
     * renewals/changes made outside this one request.
     */
    public function subscribe(Request $request, SubscriptionPlan $plan, SubscriptionService $subscriptions): JsonResponse
    {
        abort_if($plan->price_minor === 0, 404);

        if (! $plan->isRoleAgnostic() && ! $request->user()->hasRole($plan->role_scope->value)) {
            abort(403);
        }

        $data = $request->validate([
            'payment_method' => ['required', 'string'],
            'billing_period' => ['required', Rule::in(['monthly', 'annual'])],
        ]);

        $stripePriceId = $plan->stripePriceIdFor($data['billing_period']);

        abort_if(! $stripePriceId, 503, "Ce plan n'est pas encore configuré pour la facturation.");

        try {
            $request->user()
                ->newSubscription('default', $stripePriceId)
                ->trialDays(self::TRIAL_DAYS)
                ->create($data['payment_method']);
        } catch (IncompletePayment $e) {
            return response()->json(['requiresAction' => true, 'paymentId' => $e->payment->id]);
        }

        $cashierSubscription = $request->user()->subscription('default');

        $subscriptions->syncPlanFromCashierSubscription(
            $request->user(),
            $plan,
            $cashierSubscription->stripe_id,
            $data['billing_period'],
            $cashierSubscription->trial_ends_at,
        );

        return response()->json(['status' => 'active']);
    }

    /**
     * PayPal and Mobile Money — these can't go through Cashier (Stripe
     * only), so they stay on the app's own one-off CreatePayment flow,
     * same as before Cashier. This is a one-time charge, not a recurring
     * subscription: nothing here auto-renews next month, unlike the card
     * rail. ActivateSubscriptionOnPayment switches the plan once the
     * provider's webhook confirms the payment succeeded.
     */
    public function pay(Request $request, SubscriptionPlan $plan, CreatePayment $action): RedirectResponse
    {
        abort_if($plan->price_minor === 0, 404);

        if (! $plan->isRoleAgnostic() && ! $request->user()->hasRole($plan->role_scope->value)) {
            abort(403);
        }

        $data = $request->validate([
            'provider' => ['required', Rule::in(['paypal', 'orange_money', 'mpesa', 'airtel_money'])],
            'phone_number' => ['required_if:provider,orange_money,mpesa,airtel_money', 'nullable', 'string'],
            'billing_period' => ['required', Rule::in(['monthly', 'annual'])],
        ]);

        $provider = PaymentProviderName::from($data['provider']);
        $amountMinor = $plan->priceMinorFor($data['billing_period']);

        abort_if(! $amountMinor, 503, "Ce plan n'est pas encore configuré pour la facturation annuelle.");

        try {
            [, $intent] = $action->handle(
                payer: $request->user(),
                purpose: PaymentPurpose::Subscription,
                provider: $provider,
                amountMinor: $amountMinor,
                currency: $plan->currency,
                payable: $plan,
                // PayPal/Mobile Money never renew automatically (see class
                // docblock) — billing_period here is only recorded so
                // ActivateSubscriptionOnPayment stamps the right period on
                // the resulting plan_subscriptions row, not because this
                // charge repeats.
                metadata: array_filter(['phone_number' => $data['phone_number'] ?? null, 'billing_period' => $data['billing_period']]),
                // One idempotency key per (user, plan) pair per request —
                // a double-click on "Pay" reuses the same Payment instead
                // of charging twice (spec §41).
                idempotencyKey: "sub-{$request->user()->id}-{$plan->id}-".now()->format('YmdHis'),
            );
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['provider' => "Ce moyen de paiement n'est pas disponible pour le moment."]);
        }

        if ($intent->redirectUrl) {
            return redirect()->away($intent->redirectUrl);
        }

        return redirect()->route('settings.billing.checkout', $plan)
            ->with('success', 'Paiement initié — finalisez-le depuis votre moyen de paiement.');
    }
}
