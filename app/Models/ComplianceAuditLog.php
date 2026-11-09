<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ComplianceAuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'compliance_case_id',
        'actor_user_id',
        'event',
        'subject_type',
        'subject_id',
        'context',
        'ip_address',
        'user_agent',
        'legal_retention_required',
        'retention_until',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'legal_retention_required' => 'boolean',
            'retention_until' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function complianceCase(): BelongsTo
    {
        return $this->belongsTo(ComplianceCase::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
