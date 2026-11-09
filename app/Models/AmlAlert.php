<?php

namespace App\Models;

use App\Enums\AmlAlertStatus;
use App\Enums\AmlAlertType;
use App\Enums\AmlRiskLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AmlAlert extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'compliance_case_id',
        'alert_type',
        'severity',
        'status',
        'source',
        'title',
        'description',
        'metadata',
        'assigned_to_user_id',
        'resolution_decision',
        'resolution_comment',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'alert_type' => AmlAlertType::class,
            'severity' => AmlRiskLevel::class,
            'status' => AmlAlertStatus::class,
            'metadata' => 'array',
            'resolved_at' => 'datetime',
        ];
    }

    public function complianceCase(): BelongsTo
    {
        return $this->belongsTo(ComplianceCase::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [AmlAlertStatus::Open, AmlAlertStatus::UnderInvestigation, AmlAlertStatus::Escalated], true);
    }
}
