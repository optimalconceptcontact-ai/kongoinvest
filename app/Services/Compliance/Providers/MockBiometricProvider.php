<?php

namespace App\Services\Compliance\Providers;

use App\Contracts\Compliance\BiometricProviderContract;
use App\Models\KycDocument;
use App\Models\KycProfile;

class MockBiometricProvider implements BiometricProviderContract
{
    public function verifyBiometrics(KycProfile $profile, string $selfiePath, ?KycDocument $document = null): array
    {
        // Deterministic simulation based on profile flag or default high confidence
        $livenessPassed = true;
        $livenessScore = 0.98;
        $faceMatchPassed = true;
        $faceMatchScore = 0.94;

        return [
            'provider' => 'mock_biometrics_v1',
            'liveness_passed' => $livenessPassed,
            'liveness_score' => $livenessScore,
            'face_match_passed' => $faceMatchPassed,
            'face_match_score' => $faceMatchScore,
            'biometric_meta' => [
                'model_version' => '3.2.0',
                'device_risk' => 'low',
                'timestamp' => now()->toIso8601String(),
            ],
        ];
    }
}
