<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SourceOfWealthDeclaration extends Model
{
    use HasFactory;

    protected $fillable = [
        'compliance_case_id',
        'user_id',
        'estimated_total_net_worth_minor',
        'currency',
        'wealth_description',
        'wealth_components',
        'supporting_document_paths',
    ];

    protected function casts(): array
    {
        return [
            'estimated_total_net_worth_minor' => 'integer',
            'wealth_components' => 'array',
            'supporting_document_paths' => 'array',
            'is_verified' => 'boolean',
        ];
    }

    public function complianceCase(): BelongsTo
    {
        return $this->belongsTo(ComplianceCase::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
