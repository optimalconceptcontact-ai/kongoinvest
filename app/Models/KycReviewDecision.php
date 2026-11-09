<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KycReviewDecision extends Model
{
    use HasFactory;

    protected $fillable = [
        'kyc_profile_id',
        'reviewer_user_id',
        'decision',
        'notes',
        'requested_fields_or_docs',
    ];

    protected function casts(): array
    {
        return [
            'requested_fields_or_docs' => 'array',
        ];
    }

    public function kycProfile(): BelongsTo
    {
        return $this->belongsTo(KycProfile::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_user_id');
    }
}
