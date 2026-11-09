<?php

namespace App\Models;

use App\Enums\AmlRiskLevel;
use App\Enums\KycStatus;
use Database\Factories\KycProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class KycProfile extends Model implements HasMedia
{
    /** @use HasFactory<KycProfileFactory> */
    use HasFactory, InteractsWithMedia;

    // Mass-assignment guarded: status, reviews, risk_level, and audit states are set via trusted Actions / Services
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'postnom',
        'date_of_birth',
        'place_of_birth',
        'nationalities',
        'residential_address',
        'residential_country',
        'phone',
        'email',
        'profession',
        'employer_or_activity',
        'is_pep',
        'pep_function',
        'pep_start_date',
        'pep_end_date',
        'is_acting_for_third_party',
        'third_party_details',
    ];

    protected function casts(): array
    {
        return [
            'status' => KycStatus::class,
            'risk_level' => AmlRiskLevel::class,
            'nationalities' => 'array',
            'date_of_birth' => 'date',
            'pep_start_date' => 'date',
            'pep_end_date' => 'date',
            'is_pep' => 'boolean',
            'is_acting_for_third_party' => 'boolean',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'last_verified_at' => 'datetime',
            'valid_until' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function complianceCase(): BelongsTo
    {
        return $this->belongsTo(ComplianceCase::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(KycVerification::class)->latest();
    }

    public function latestVerification(): HasOne
    {
        return $this->hasOne(KycVerification::class)->latestOfMany();
    }

    public function documents(): HasMany
    {
        return $this->hasMany(KycDocument::class);
    }

    public function biometricChecks(): HasMany
    {
        return $this->hasMany(KycBiometricCheck::class);
    }

    public function reviewDecisions(): HasMany
    {
        return $this->hasMany(KycReviewDecision::class)->latest();
    }

    public function registerMediaCollections(): void
    {
        // Private disk only — spec §42-43. Never served directly; always
        // through a signed, time-limited, audit-logged URL.
        $this->addMediaCollection('kyc-documents')
            ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png'])
            ->useDisk('private');
    }

    public function fullName(): string
    {
        $parts = array_filter([$this->first_name, $this->last_name, $this->postnom]);
        if (empty($parts)) {
            return $this->user?->name ?? 'Dossier KYC';
        }

        return implode(' ', $parts);
    }

    public function isVerifiedAndActive(): bool
    {
        if ($this->status !== KycStatus::Approved) {
            return false;
        }

        if ($this->valid_until !== null && $this->valid_until->isPast()) {
            return false;
        }

        return true;
    }
}
