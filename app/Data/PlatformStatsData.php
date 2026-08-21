<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class PlatformStatsData extends Data
{
    public function __construct(
        public int $publishedProjects,
        public int $entrepreneurs,
        public int $investors,
        public int $sectors,
        public int $investorCountries,
    ) {}
}
