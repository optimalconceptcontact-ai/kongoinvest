<?php

namespace App\Actions\Documents;

use App\Enums\DocumentVisibility;
use App\Models\Document;
use App\Models\User;
use App\Services\AuditLogger;

class RequestDocumentAccess
{
    public function __construct(private AuditLogger $audit) {}

    public function handle(User $requester, Document $document): Document
    {
        // visibility/requested_by_user_id are deliberately outside
        // Document::$fillable — update() would silently drop them.
        $document->forceFill([
            'visibility' => DocumentVisibility::Requested->value,
            'requested_by_user_id' => $requester->id,
        ])->save();

        $this->audit->log($requester, 'document.access_requested', $document);

        return $document->fresh();
    }
}
