<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdverseMediaScreening extends Model
{
    use HasFactory;

    protected $fillable = [
        'compliance_case_id',
        'subject_name',
        'provider',
        'has_hits',
        'hit_count',
        'hits_details',
        'is_reviewed',
        'analyst_notes',
        'reviewed_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'has_hits' => 'boolean',
            'hit_count' => 'integer',
            'hits_details' => 'array',
            'is_reviewed' => 'boolean',
        ];
    }

    public function complianceCase(): BelongsTo
    {
        return $this->belongsTo(ComplianceCase::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }
}
