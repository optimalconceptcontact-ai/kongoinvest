<?php

namespace App\Models;

use App\Enums\AmlRiskLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AmlRiskAssessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'compliance_case_id',
        'rule_version_id',
        'client_nature_score',
        'ubo_complexity_score',
        'geographic_score',
        'sectoral_score',
        'product_channel_score',
        'source_of_funds_score',
        'transaction_behavior_score',
        'overall_score',
        'risk_level',
        'requires_enhanced_due_diligence',
        'mitigating_factors',
        'analyst_rationale',
        'assessed_by_user_id',
        'assessed_at',
    ];

    protected function casts(): array
    {
        return [
            'client_nature_score' => 'integer',
            'ubo_complexity_score' => 'integer',
            'geographic_score' => 'integer',
            'sectoral_score' => 'integer',
            'product_channel_score' => 'integer',
            'source_of_funds_score' => 'integer',
            'transaction_behavior_score' => 'integer',
            'overall_score' => 'integer',
            'risk_level' => AmlRiskLevel::class,
            'requires_enhanced_due_diligence' => 'boolean',
            'assessed_at' => 'datetime',
        ];
    }

    public function complianceCase(): BelongsTo
    {
        return $this->belongsTo(ComplianceCase::class);
    }

    public function ruleVersion(): BelongsTo
    {
        return $this->belongsTo(ComplianceRuleVersion::class, 'rule_version_id');
    }

    public function assessedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by_user_id');
    }
}
