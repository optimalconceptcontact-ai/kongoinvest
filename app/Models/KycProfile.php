<?php

namespace App\Models;

use App\Enums\KycStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class KycProfile extends Model implements HasMedia
{
    /** @use HasFactory<\Database\Factories\KycProfileFactory> */
    use HasFactory, InteractsWithMedia;

    protected $fillable = ['user_id']; // status/timestamps are set only via SubmitKyc / ReviewKyc actions

    protected function casts(): array
    {
        return [
            'status' => KycStatus::class,
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function registerMediaCollections(): void
    {
        // Private disk only — spec §42-43. Never served directly; always
        // through a signed, time-limited, audit-logged URL (see
        // KycController::download() and Phase 9's README section).
        $this->addMediaCollection('kyc-documents')
            ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png'])
            ->useDisk('private');
    }
}
