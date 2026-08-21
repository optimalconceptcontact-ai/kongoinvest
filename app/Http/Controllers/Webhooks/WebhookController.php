<?php

namespace App\Http\Controllers\Webhooks;

use App\Actions\Payments\ProcessWebhookEvent;
use App\Enums\PaymentProviderName;
use App\Http\Controllers\Controller;
use App\Services\Payments\PaymentProviderFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * These routes are deliberately outside the `auth` middleware — the
 * caller is Stripe/PayPal/a Mobile Money operator's servers, not a
 * logged-in user. They are NOT unauthenticated in any meaningful sense
 * though: every provider's handleWebhook() verifies a cryptographic
 * signature before ProcessWebhookEvent touches anything (spec §40). A
 * request without a valid signature never reaches the database.
 */
class WebhookController extends Controller
{
    public function __construct(
        private PaymentProviderFactory $providers,
        private ProcessWebhookEvent $process,
    ) {}

    public function stripe(Request $request): Response
    {
        return $this->handle($request, PaymentProviderName::Stripe);
    }

    public function paypal(Request $request): Response
    {
        return $this->handle($request, PaymentProviderName::PayPal);
    }

    public function orangeMoney(Request $request): Response
    {
        return $this->handle($request, PaymentProviderName::OrangeMoney);
    }

    public function mpesa(Request $request): Response
    {
        return $this->handle($request, PaymentProviderName::Mpesa);
    }

    public function airtelMoney(Request $request): Response
    {
        return $this->handle($request, PaymentProviderName::AirtelMoney);
    }

    private function handle(Request $request, PaymentProviderName $providerName): Response
    {
        $event = $this->providers->make($providerName)->handleWebhook($request);

        if ($event === null) {
            // Deliberately generic — never confirm *why* verification
            // failed (bad signature vs missing payment vs malformed
            // payload) to an unauthenticated caller; that's information
            // an attacker probing the endpoint shouldn't get for free.
            return response('', 400);
        }

        $this->process->handle($event);

        return response('', 200);
    }
}
