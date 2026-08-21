<?php

namespace App\Services\Payments\Providers;

/**
 * A catch-all for regional aggregators (e.g. a single integration that
 * itself routes to multiple Mobile Money operators) — same generic
 * shared-secret webhook scheme as the individual operators, since this
 * is exactly the same shape of integration one level removed.
 */
class AggregatorPaymentProvider extends AbstractMobileMoneyProvider
{
    protected function configPrefix(): string
    {
        return 'aggregator';
    }

    protected function baseUrl(): string
    {
        return config('services.aggregator.base_url', '');
    }
}
