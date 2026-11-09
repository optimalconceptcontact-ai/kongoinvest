<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KycDocumentCheck extends Model
{
    use HasFactory;

    protected $fillable = [
        'kyc_document_id',
        'provider',
        'is_readable',
        'is_expired',
        'is_duplicate',
        'tampering_detected',
        'data_matches_form',
        'extracted_fields',
        'field_confidence_scores',
        'overall_ocr_score',
        'inconsistencies_detected',
        'check_summary',
    ];

    protected function casts(): array
    {
        return [
            'is_readable' => 'boolean',
            'is_expired' => 'boolean',
            'is_duplicate' => 'boolean',
            'tampering_detected' => 'boolean',
            'data_matches_form' => 'boolean',
            'extracted_fields' => 'array',
            'field_confidence_scores' => 'array',
            'overall_ocr_score' => 'float',
            'inconsistencies_detected' => 'array',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(KycDocument::class, 'kyc_document_id');
    }
}
