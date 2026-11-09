<?php

namespace App\Models;

use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class KycDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'kyc_profile_id',
        'kyc_verification_id',
        'document_type',
        'issuing_country',
        'document_number_encrypted',
        'document_number_hash',
        'issued_at',
        'expires_at',
        'front_file_path',
        'back_file_path',
        'file_hash',
        'status',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'document_type' => DocumentType::class,
            'document_number_encrypted' => 'encrypted',
            'issued_at' => 'date',
            'expires_at' => 'date',
        ];
    }

    public function kycProfile(): BelongsTo
    {
        return $this->belongsTo(KycProfile::class);
    }

    public function verification(): BelongsTo
    {
        return $this->belongsTo(KycVerification::class, 'kyc_verification_id');
    }

    public function checks(): HasMany
    {
        return $this->hasMany(KycDocumentCheck::class);
    }

    public function latestCheck(): HasOne
    {
        return $this->hasOne(KycDocumentCheck::class)->latestOfMany();
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
