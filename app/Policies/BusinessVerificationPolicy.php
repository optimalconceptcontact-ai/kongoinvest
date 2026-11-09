<?php

namespace App\Policies;

use App\Models\BusinessVerification;
use App\Models\User;

class BusinessVerificationPolicy
{
    public function view(User $user, BusinessVerification $verification): bool
    {
        return $verification->legal_representative_user_id === $user->id
            || $verification->company?->legalRecords()->where('representative_user_id', $user->id)->exists()
            || $user->can('kyc.review')
            || $user->hasRole('admin');
    }

    public function update(User $user, BusinessVerification $verification): bool
    {
        return $verification->legal_representative_user_id === $user->id
            || $user->can('kyc.review')
            || $user->hasRole('admin');
    }

    public function review(User $user): bool
    {
        return $user->can('kyc.review') || $user->hasRole('admin');
    }
}
