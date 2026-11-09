<?php

namespace App\Models;

use App\Enums\AmlRiskLevel;
use App\Enums\ComplianceCaseStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ComplianceCase extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'case_number',
        'user_id',
        'subject_type',
        'subject_id',
        'notes',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $case) {
            if (empty($case->case_number)) {
                $case->case_number = 'CASE-'.strtoupper(bin2hex(random_bytes(4)));
            }
        });
    }

    protected function casts(): array
    {
        return [
            'status' => ComplianceCaseStatus::class,
            'risk_level' => AmlRiskLevel::class,
            'risk_score' => 'integer',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'expires_at' => 'datetime',
            'suspended_at' => 'datetime',
            'blocked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function ruleVersion(): BelongsTo
    {
        return $this->belongsTo(ComplianceRuleVersion::class, 'rule_version_id');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(ComplianceCaseAction::class)->latest();
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(ComplianceApproval::class);
    }

    public function providerChecks(): HasMany
    {
        return $this->hasMany(ComplianceProviderCheck::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(AmlAlert::class);
    }

    public function riskAssessments(): HasMany
    {
        return $this->hasMany(AmlRiskAssessment::class)->latest();
    }

    public function latestRiskAssessment(): ?AmlRiskAssessment
    {
        return $this->riskAssessments()->first();
    }

    public function isBlockedOrSuspended(): bool
    {
        return in_array($this->status, [ComplianceCaseStatus::Blocked, ComplianceCaseStatus::Suspended], true);
    }

    public function isApproved(): bool
    {
        return in_array($this->status, [ComplianceCaseStatus::Approved, ComplianceCaseStatus::ApprovedWithConditions], true);
    }
}
