<?php

namespace App\Contracts\Compliance;

use App\Models\KycDocument;
use App\Models\KycProfile;

interface BiometricProviderContract
{
    /**
     * Compare user selfie with identity document photo and check liveness.
     *
     * @return array{
     *     provider: string,
     *     liveness_passed: bool,
     *     liveness_score: float,
     *     face_match_passed: bool,
     *     face_match_score: float,
     *     biometric_meta: array<string, mixed>
     * }
     */
    public function verifyBiometrics(KycProfile $profile, string $selfiePath, ?KycDocument $document = null): array;
}
