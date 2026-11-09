<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplianceProviderCheck extends Model
{
    use HasFactory;

    protected $fillable = [
        'compliance_case_id',
        'provider',
        'check_type',
        'reference_id',
        'status',
        'request_payload',
        'response_payload',
        'execution_time_ms',
    ];

    protected function casts(): array
    {
        return [
            'request_payload' => 'array',
            'response_payload' => 'array',
            'execution_time_ms' => 'integer',
        ];
    }

    public function complianceCase(): BelongsTo
    {
        return $this->belongsTo(ComplianceCase::class);
    }
}
