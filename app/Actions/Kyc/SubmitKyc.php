<?php

namespace App\Actions\Kyc;

use App\Enums\KycStatus;
use App\Models\KycProfile;
use App\Services\AuditLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubmitKyc
{
    public function __construct(private AuditLogger $audit) {}

    /** @param  UploadedFile[]  $files */
    public function handle(KycProfile $kycProfile, array $files): KycProfile
    {
        if (! in_array($kycProfile->status, [KycStatus::Draft, KycStatus::Rejected], true)) {
            throw ValidationException::withMessages([
                'kyc' => 'Ce dossier KYC ne peut pas être resoumis dans son état actuel.',
            ]);
        }

        if (empty($files)) {
            throw ValidationException::withMessages([
                'files' => 'Ajoutez au moins un document avant de soumettre.',
            ]);
        }

        return DB::transaction(function () use ($kycProfile, $files) {
            foreach ($files as $file) {
                $kycProfile->addMedia($file)->toMediaCollection('kyc-documents');
            }

            // status/submitted_at/rejection_reason are deliberately excluded
            // from KycProfile::$fillable (only SubmitKyc/ReviewKyc may set
            // them) — update() would silently no-op on them.
            $kycProfile->status = KycStatus::Submitted->value;
            $kycProfile->submitted_at = now();
            $kycProfile->rejection_reason = null;
            $kycProfile->save();

            $this->audit->log($kycProfile->user, 'kyc.submitted', $kycProfile, [
                'file_count' => count($files),
            ]);

            return $kycProfile->fresh();
        });
    }
}
