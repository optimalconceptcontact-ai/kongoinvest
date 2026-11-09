<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Analytics timezone
    |--------------------------------------------------------------------------
    |
    | All period boundaries are computed in this timezone, then converted to
    | UTC for database queries (the app stores timestamps in UTC). Kinshasa
    | and Lubumbashi both use UTC+2 without daylight saving time, so a single
    | reference timezone is correct for the whole DRC.
    |
    */

    'timezone' => env('STATISTICS_TIMEZONE', 'Africa/Kinshasa'),

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | 0 disables aggregation caching. The current data volume does not justify
    | caching; the key builder is still in place so a TTL can be enabled later
    | without changing the services.
    |
    */

    'cache_ttl_seconds' => (int) env('STATISTICS_CACHE_TTL', 0),

    /*
    |--------------------------------------------------------------------------
    | Account activity
    |--------------------------------------------------------------------------
    |
    | A user is considered "active" for the account status column when their
    | last recorded activity is within this window. Period KPIs use the
    | selected period instead of this window.
    |
    */

    'activity_window_days' => (int) env('STATISTICS_ACTIVITY_WINDOW_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Exports
    |--------------------------------------------------------------------------
    */

    'export_row_limit' => (int) env('STATISTICS_EXPORT_ROW_LIMIT', 5000),

    /*
    |--------------------------------------------------------------------------
    | Currencies
    |--------------------------------------------------------------------------
    |
    | Supported currencies for filtering and per-currency totals. Amounts in
    | different currencies are never summed together and no FX conversion is
    | applied anywhere in the statistics module.
    |
    */

    'currencies' => ['USD', 'CDF'],

];
