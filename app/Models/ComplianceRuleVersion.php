<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ComplianceRuleVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'version',
        'description',
        'weights',
        'thresholds',
        'is_active',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'weights' => 'array',
            'thresholds' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function complianceCases(): HasMany
    {
        return $this->hasMany(ComplianceCase::class, 'rule_version_id');
    }
}
