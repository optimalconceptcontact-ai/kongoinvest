<?php

namespace App\Actions\Kyc;

use App\Enums\AmlAlertStatus;
use App\Enums\AmlAlertType;
use App\Enums\KycStatus;
use App\Events\KycApproved;
use App\Events\KycRejected;
use App\Models\KycProfile;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Validation\ValidationException;

class ReviewKyc
{
    public function __construct(private AuditLogger $audit) {}

    public function approve(User $reviewer, KycProfile $kycProfile): KycProfile
    {
        $this->assertReviewable($kycProfile);

        // These columns are deliberately excluded from KycProfile::$fillable
        // (only SubmitKyc/ReviewKyc may set them) — update() would
        // silently no-op on them.
        $kycProfile->status = KycStatus::Approved->value;
        $kycProfile->reviewed_at = now();
        $kycProfile->reviewed_by_user_id = $reviewer->id;
        $kycProfile->rejection_reason = null;
        $kycProfile->save();

        // KYC approval is exactly the kind of sensitive action spec §49
        // requires an audit trail for — who approved whose identity
        // documents, and when.
        $this->audit->log($reviewer, 'kyc.approved', $kycProfile, ['subject_user_id' => $kycProfile->user_id]);

        $kycProfile = $kycProfile->fresh();
        KycApproved::dispatch($kycProfile);

        return $kycProfile;
    }

    public function reject(User $reviewer, KycProfile $kycProfile, string $reason): KycProfile
    {
        $this->assertReviewable($kycProfile);

        $kycProfile->status = KycStatus::Rejected->value;
        $kycProfile->reviewed_at = now();
        $kycProfile->reviewed_by_user_id = $reviewer->id;
        $kycProfile->rejection_reason = $reason;
        $kycProfile->save();

        $this->audit->log($reviewer, 'kyc.rejected', $kycProfile, [
            'subject_user_id' => $kycProfile->user_id,
            'reason' => $reason,
        ]);

        $kycProfile = $kycProfile->fresh();
        KycRejected::dispatch($kycProfile);

        return $kycProfile;
    }

    private function assertReviewable(KycProfile $kycProfile): void
    {
        $case = $kycProfile->complianceCase;

        if ($case?->isBlockedOrSuspended()) {
            throw ValidationException::withMessages([
                'kyc' => 'Ce dossier fait l\'objet d\'un blocage ou d\'une suspension : il doit être traité par le service conformité.',
            ]);
        }

        if ($case && $case->alerts()
            ->where('status', AmlAlertStatus::Open)
            ->where('alert_type', AmlAlertType::SanctionsMatch)
            ->exists()) {
            throw ValidationException::withMessages([
                'kyc' => 'Une alerte sanctions reste ouverte : elle doit être traitée avant toute décision KYC.',
            ]);
        }

        if (! in_array($kycProfile->status, [KycStatus::Submitted, KycStatus::UnderReview], true)) {
            throw ValidationException::withMessages([
                'kyc' => "Ce dossier n'est pas en attente de vérification.",
            ]);
        }
    }
}
