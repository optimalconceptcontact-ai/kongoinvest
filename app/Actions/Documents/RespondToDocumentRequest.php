<?php

namespace App\Actions\Documents;

use App\Enums\DocumentVisibility;
use App\Models\Document;
use App\Models\User;
use App\Services\AuditLogger;

class RespondToDocumentRequest
{
    public function __construct(private AuditLogger $audit) {}

    public function approve(User $owner, Document $document): Document
    {
        // visibility is deliberately outside Document::$fillable —
        // update() would silently drop it.
        $document->forceFill(['visibility' => DocumentVisibility::Shared->value])->save();

        $this->audit->log($owner, 'document.share_approved', $document);

        return $document->fresh();
    }

    public function reject(User $owner, Document $document): Document
    {
        $document->forceFill(['visibility' => DocumentVisibility::Rejected->value])->save();

        $this->audit->log($owner, 'document.share_rejected', $document);

        return $document->fresh();
    }
}
