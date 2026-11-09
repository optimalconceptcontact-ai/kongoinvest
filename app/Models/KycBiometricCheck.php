<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KycBiometricCheck extends Model
{
    use HasFactory;

    protected $fillable = [
        'kyc_profile_id',
        'kyc_document_id',
        'selfie_file_path',
        'provider',
        'liveness_passed',
        'liveness_score',
        'face_match_passed',
        'face_match_score',
        'biometric_meta',
    ];

    protected function casts(): array
    {
        return [
            'liveness_passed' => 'boolean',
            'liveness_score' => 'float',
            'face_match_passed' => 'boolean',
            'face_match_score' => 'float',
            'biometric_meta' => 'array',
        ];
    }

    public function kycProfile(): BelongsTo
    {
        return $this->belongsTo(KycProfile::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(KycDocument::class, 'kyc_document_id');
    }
}
