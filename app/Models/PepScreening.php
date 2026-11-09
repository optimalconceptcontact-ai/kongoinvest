<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PepScreening extends Model
{
    use HasFactory;

    protected $fillable = [
        'compliance_case_id',
        'subject_name',
        'subject_country',
        'provider',
        'has_match',
        'match_type',
        'match_score',
        'pep_role_title',
        'pep_jurisdiction',
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
