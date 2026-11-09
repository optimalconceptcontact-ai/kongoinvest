<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KycVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'kyc_profile_id',
        'compliance_case_id',
        'operation_reference',
    ];

    protected function casts(): array
    {
        return [
            'overall_confidence_score' => 'integer',
            'requires_manual_review' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    public function kycProfile(): BelongsTo
    {
        return $this->belongsTo(KycProfile::class);
    }

    public function complianceCase(): BelongsTo
    {
        return $this->belongsTo(ComplianceCase::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(KycDocument::class);
    }
}
