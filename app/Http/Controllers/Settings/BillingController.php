<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Payments\CreatePayment;
use App\Enums\PaymentProviderName;
use App\Enums\PaymentPurpose;
use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    public function checkout(Request $request, SubscriptionPlan $plan): Response
    {
        abort_if($plan->price_minor === 0, 404); // Free never goes through checkout

        if (! $plan->isRoleAgnostic() && ! $request->user()->hasRole($plan->role_scope->value)) {
            abort(403, "Ce plan n'est pas disponible pour votre rôle.");
        }

        return Inertia::render('Settings/Billing/Checkout', [
            'plan' => $plan->only(['id', 'key', 'name', 'price_minor', 'currency']),
        ]);
    }

    /**
     * Card rail only. Returns a real Stripe PaymentIntent client secret
     * as JSON — the frontend mounts Stripe Elements against it and
     * collects card details itself, through Stripe's own hosted iframe
     * fields. The raw card number/expiry/CVC never touch this endpoint,
     * this controller, or our database at any point (PCI scope stays
     * entirely with Stripe, by construction — not just by policy).
     */
    public function intent(Request $request, SubscriptionPlan $plan, CreatePayment $action): JsonResponse
    {
        abort_if($plan->price_minor === 0, 404);

        if (! $plan->isRoleAgnostic() && ! $request->user()->hasRole($plan->role_scope->value)) {
            abort(403);
        }

        try {
            [, $intent] = $action->handle(
                payer: $request->user(),
                purpose: PaymentPurpose::Subscription,
                provider: PaymentProviderName::Stripe,
                amountMinor: $plan->price_minor,
                currency: $plan->currency,
                payable: $plan,
                idempotencyKey: "sub-{$request->user()->id}-{$plan->id}-".now()->format('YmdHisv'),
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => "Le paiement par carte n'est pas disponible pour le moment."], 503);
        }

        return response()->json(['clientSecret' => $intent->clientSecret]);
    }

    /**
     * PayPal and Mobile Money only now — both are redirect/async flows
     * with nothing to collect on our own page, unlike Stripe. See
     * intent() above for the card rail.
     */
    public function pay(Request $request, SubscriptionPlan $plan, CreatePayment $action): RedirectResponse
    {
        abort_if($plan->price_minor === 0, 404);

        $data = $request->validate([
            'provider' => ['required', Rule::in(['paypal', 'orange_money', 'mpesa', 'airtel_money'])],
            'phone_number' => ['required_if:provider,orange_money,mpesa,airtel_money', 'nullable', 'string'],
        ]);

        $provider = PaymentProviderName::from($data['provider']);

        try {
            [, $intent] = $action->handle(
                payer: $request->user(),
                purpose: PaymentPurpose::Subscription,
                provider: $provider,
                amountMinor: $plan->price_minor,
                currency: $plan->currency,
                payable: $plan,
                metadata: array_filter(['phone_number' => $data['phone_number'] ?? null]),
                // One idempotency key per (user, plan) pair per request —
                // a double-click on "Pay" reuses the same Payment instead
                // of charging twice (spec §41).
                idempotencyKey: "sub-{$request->user()->id}-{$plan->id}-".now()->format('YmdHis'),
            );
        } catch (\RuntimeException $e) {
            // A provider that isn't configured (no API key set) fails
            // loudly in development rather than silently pretending to
            // work — but the person checking out just sees a clean
            // error, not a stack trace (spec §137).
            throw ValidationException::withMessages(['provider' => "Ce moyen de paiement n'est pas disponible pour le moment."]);
        }

        if ($intent->redirectUrl) {
            return redirect()->away($intent->redirectUrl);
        }

        return redirect()->route('settings.billing.checkout', $plan)
            ->with('success', 'Paiement initié — finalisez-le depuis votre moyen de paiement.');
    }
}
