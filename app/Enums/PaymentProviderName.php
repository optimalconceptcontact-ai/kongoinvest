<?php

namespace App\Enums;

enum PaymentProviderName: string
{
    case Stripe = 'stripe';
    case StripeConnect = 'stripe_connect';
    case PayPal = 'paypal';
    case OrangeMoney = 'orange_money';
    case Mpesa = 'mpesa';
    case AirtelMoney = 'airtel_money';
    case Aggregator = 'aggregator';

    public function label(): string
    {
        return match ($this) {
            self::Stripe => 'Stripe',
            self::StripeConnect => 'Stripe Connect',
            self::PayPal => 'PayPal',
            self::OrangeMoney => 'Orange Money',
            self::Mpesa => 'M-Pesa',
            self::AirtelMoney => 'Airtel Money',
            self::Aggregator => 'Agrégateur',
        };
    }

    /** Card-rail providers, billed in any supported currency. */
    public static function cardProviders(): array
    {
        return [self::Stripe, self::PayPal];
    }

    /** Mobile Money — realistically USD/CDF only, RDC + neighboring markets. */
    public static function mobileMoneyProviders(): array
    {
        return [self::OrangeMoney, self::Mpesa, self::AirtelMoney];
    }
}
