<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function view(User $user, Document $document): bool
    {
        return $document->isVisibleTo($user) || $user->can('users.manage');
    }

    public function manage(User $user, Document $document): bool
    {
        return $document->owner_id === $user->id;
    }

    /** Only the deal room participant who is NOT the owner may request access. */
    public function request(User $user, Document $document): bool
    {
        return $document->owner_id !== $user->id && $document->dealRoom->isParticipant($user);
    }
}
