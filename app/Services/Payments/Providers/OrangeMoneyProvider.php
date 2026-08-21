<?php

namespace App\Services\Payments\Providers;

class OrangeMoneyProvider extends AbstractMobileMoneyProvider
{
    protected function configPrefix(): string
    {
        return 'orange_money';
    }

    protected function baseUrl(): string
    {
        return config('services.orange_money.base_url', 'https://api.orange.com/orange-money-webpay/dev/v1');
    }
}
