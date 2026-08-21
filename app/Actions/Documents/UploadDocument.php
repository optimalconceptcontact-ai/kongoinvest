<?php

namespace App\Actions\Documents;

use App\Enums\DocumentVisibility;
use App\Models\DealRoom;
use App\Models\Document;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class UploadDocument
{
    public function __construct(private AuditLogger $audit) {}

    public function handle(User $owner, DealRoom $dealRoom, string $title, UploadedFile $file, bool $shareImmediately = false): Document
    {
        return DB::transaction(function () use ($owner, $dealRoom, $title, $file, $shareImmediately) {
            // owner_id/visibility are deliberately outside
            // Document::$fillable (only 'title' is) — create() would
            // silently drop them.
            $document = $dealRoom->documents()->forceCreate([
                'owner_id' => $owner->id,
                'title' => $title,
                'visibility' => $shareImmediately ? DocumentVisibility::Shared->value : DocumentVisibility::Private->value,
            ]);

            $document->addMedia($file)->toMediaCollection('deal-documents');

            $this->audit->log($owner, 'document.uploaded', $document, ['deal_room_id' => $dealRoom->id]);

            return $document->fresh();
        });
    }
}
