<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SanctionsScreening extends Model
{
    use HasFactory;

    protected $fillable = [
        'compliance_case_id',
        'subject_name',
        'subject_type',
        'provider',
        'has_match',
        'match_score',
        'matched_lists',
        'raw_matches',
        'is_false_positive',
        'resolution_notes',
        'resolved_by_user_id',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'has_match' => 'boolean',
            'match_score' => 'float',
            'matched_lists' => 'array',
            'raw_matches' => 'array',
            'is_false_positive' => 'boolean',
            'resolved_at' => 'datetime',
        ];
    }

    public function complianceCase(): BelongsTo
    {
        return $this->belongsTo(ComplianceCase::class);
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }
}
