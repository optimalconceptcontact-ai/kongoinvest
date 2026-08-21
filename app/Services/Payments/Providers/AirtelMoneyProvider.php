<?php

namespace App\Services\Payments\Providers;

class AirtelMoneyProvider extends AbstractMobileMoneyProvider
{
    protected function configPrefix(): string
    {
        return 'airtel_money';
    }

    protected function baseUrl(): string
    {
        return config('services.airtel_money.base_url', 'https://openapi.airtel.africa');
    }
}
