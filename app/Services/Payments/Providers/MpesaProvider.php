<?php

namespace App\Services\Payments\Providers;

class MpesaProvider extends AbstractMobileMoneyProvider
{
    protected function configPrefix(): string
    {
        return 'mpesa';
    }

    protected function baseUrl(): string
    {
        return config('services.mpesa.base_url', 'https://api.safaricom.co.ke/mpesa');
    }
}
