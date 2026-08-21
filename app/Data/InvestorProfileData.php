<?php

namespace App\Data;

use App\Enums\FundingType;
use App\Enums\InvestmentHorizon;
use App\Enums\InvestorType;
use App\Enums\RiskAppetite;
use App\Models\InvestorProfile;
use Spatie\LaravelData\Data;

class InvestorProfileData extends Data
{
    public function __construct(
        public ?string $countryCode,
        public ?string $city,
        public ?InvestorType $investorType,
        public ?FundingType $fundingType,
        public ?InvestmentHorizon $investmentHorizon,
        public ?RiskAppetite $riskAppetite,
        public ?int $ticketMinMinor,
        public ?int $ticketMaxMinor,
        public string $ticketCurrency,
        /** @var string[] */
        public array $preferredGeographies,
        public ?string $investmentThesis,
        public bool $impactFocused,
        public bool $onboardingCompleted,
        public ?string $companyName,
        /** @var SectorData[] */
        public array $sectors,
        /** @var StageData[] */
        public array $stages,
    ) {}

    public static function fromModel(InvestorProfile $profile): self
    {
        return new self(
            countryCode: $profile->country_code,
            city: $profile->city,
            investorType: $profile->investor_type,
            fundingType: $profile->funding_type,
            investmentHorizon: $profile->investment_horizon,
            riskAppetite: $profile->risk_appetite,
            ticketMinMinor: $profile->ticket_min_minor,
            ticketMaxMinor: $profile->ticket_max_minor,
            ticketCurrency: $profile->ticket_currency,
            preferredGeographies: $profile->preferred_geographies ?? [],
            investmentThesis: $profile->investment_thesis,
            impactFocused: $profile->impact_focused,
            onboardingCompleted: $profile->onboarding_completed,
            companyName: $profile->company?->name,
            sectors: $profile->sectors->map(fn ($s) => SectorData::fromModel($s))->all(),
            stages: $profile->stages->map(fn ($s) => StageData::fromModel($s))->all(),
        );
    }
}
