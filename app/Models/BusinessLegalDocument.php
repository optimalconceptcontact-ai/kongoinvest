<?php

namespace App\Models;

use App\Enums\BusinessLegalDocumentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessLegalDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_verification_id',
        'document_type',
        'document_title',
        'file_path',
        'file_hash',
        'issue_date',
        'expiry_date',
        'is_verified',
    ];

    protected function casts(): array
    {
        return [
            'document_type' => BusinessLegalDocumentType::class,
            'issue_date' => 'date',
            'expiry_date' => 'date',
            'is_verified' => 'boolean',
        ];
    }

    public function businessVerification(): BelongsTo
    {
        return $this->belongsTo(BusinessVerification::class);
    }
}
