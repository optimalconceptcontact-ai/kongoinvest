<?php

namespace App\Models;

use App\Enums\DocumentVisibility;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Document extends Model implements HasMedia
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory, InteractsWithMedia;

    protected $fillable = ['title'];

    // deal_room_id, owner_id, visibility, requested_by_user_id are set
    // only via Actions (UploadDocument, RequestDocument,
    // RespondToDocumentRequest) — never from raw request input.

    protected function casts(): array
    {
        return [
            'visibility' => DocumentVisibility::class,
            'expires_at' => 'datetime',
        ];
    }

    public function dealRoom(): BelongsTo
    {
        return $this->belongsTo(DealRoom::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('deal-documents')
            ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
            ->useDisk('private')
            ->singleFile(); // one file per Document record — re-upload replaces it, keeping metadata (visibility, owner) stable
    }

    public function isVisibleTo(User $user): bool
    {
        if ($this->owner_id === $user->id) {
            return true;
        }

        return $this->visibility === DocumentVisibility::Shared && $this->dealRoom->isParticipant($user);
    }
}
