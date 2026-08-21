<?php

namespace App\Policies;

use App\Models\KycProfile;
use App\Models\User;

class KycPolicy
{
    public function view(User $user, KycProfile $kycProfile): bool
    {
        return $kycProfile->user_id === $user->id || $user->can('kyc.review');
    }

    public function submit(User $user, KycProfile $kycProfile): bool
    {
        return $kycProfile->user_id === $user->id && $user->can('kyc.submit');
    }

    public function review(User $user): bool
    {
        return $user->can('kyc.review');
    }
}
