<?php

namespace App\Services\Payments;

use App\Contracts\PaymentProviderInterface;
use App\Enums\PaymentProviderName;
use App\Services\Payments\Providers\AggregatorPaymentProvider;
use App\Services\Payments\Providers\AirtelMoneyProvider;
use App\Services\Payments\Providers\MpesaProvider;
use App\Services\Payments\Providers\OrangeMoneyProvider;
use App\Services\Payments\Providers\PayPalPaymentProvider;
use App\Services\Payments\Providers\StripeConnectProvider;
use App\Services\Payments\Providers\StripePaymentProvider;

class PaymentProviderFactory
{
    public function make(PaymentProviderName $provider): PaymentProviderInterface
    {
        return match ($provider) {
            PaymentProviderName::Stripe => app(StripePaymentProvider::class),
            PaymentProviderName::StripeConnect => app(StripeConnectProvider::class),
            PaymentProviderName::PayPal => app(PayPalPaymentProvider::class),
            PaymentProviderName::OrangeMoney => app(OrangeMoneyProvider::class),
            PaymentProviderName::Mpesa => app(MpesaProvider::class),
            PaymentProviderName::AirtelMoney => app(AirtelMoneyProvider::class),
            PaymentProviderName::Aggregator => app(AggregatorPaymentProvider::class),
        };
    }
}
