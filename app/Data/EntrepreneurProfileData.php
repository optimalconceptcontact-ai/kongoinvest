<?php

namespace App\Data;

use App\Enums\DrcProvince;
use App\Models\EntrepreneurProfile;
use Spatie\LaravelData\Data;

class EntrepreneurProfileData extends Data
{
    public function __construct(
        public ?string $jobTitle,
        public ?string $bio,
        public ?string $experience,
        public ?DrcProvince $province,
        public ?string $city,
        public ?int $yearsExperience,
        public bool $onboardingCompleted,
        public ?string $companyName,
        public ?string $companyWebsite,
    ) {}

    public static function fromModel(EntrepreneurProfile $profile): self
    {
        return new self(
            jobTitle: $profile->job_title,
            bio: $profile->bio,
            experience: $profile->experience,
            province: $profile->province,
            city: $profile->city,
            yearsExperience: $profile->years_experience,
            onboardingCompleted: $profile->onboarding_completed,
            companyName: $profile->company?->name,
            companyWebsite: $profile->company?->website,
        );
    }
}
