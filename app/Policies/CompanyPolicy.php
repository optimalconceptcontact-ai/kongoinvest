<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    public function view(User $user, Company $company): bool
    {
        return $this->isLinkedToCompany($user, $company)
            || $user->can('kyc.review')
            || $user->hasRole('admin');
    }

    public function update(User $user, Company $company): bool
    {
        return $this->isLinkedToCompany($user, $company)
            || $user->can('kyc.review')
            || $user->hasRole('admin');
    }

    /**
     * A company has no direct owner column. It is linked to a physical
     * person through the KYB dossier (legal representative), the legal
     * records, or the project that was converted into that company.
     */
    private function isLinkedToCompany(User $user, Company $company): bool
    {
        if ($company->businessVerifications()
            ->where('legal_representative_user_id', $user->id)
            ->exists()) {
            return true;
        }

        if ($company->legalRecords()
            ->where('representative_user_id', $user->id)
            ->exists()) {
            return true;
        }

        return $company->formationReadiness()
            ->whereHas('project', fn ($query) => $query->where('user_id', $user->id))
            ->exists();
    }
}
