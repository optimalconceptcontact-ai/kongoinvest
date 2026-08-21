<?php

namespace App\Enums;

enum DocumentVisibility: string
{
    case Private = 'private'; // only the uploader can see it
    case Shared = 'shared'; // both deal room participants can see it
    case Requested = 'requested'; // the other participant has asked the owner to share it
    case Rejected = 'rejected'; // the owner declined a share request
    case Expired = 'expired'; // access window has lapsed

    public function label(): string
    {
        return match ($this) {
            self::Private => 'Privé',
            self::Shared => 'Partagé',
            self::Requested => 'Demandé',
            self::Rejected => 'Refusé',
            self::Expired => 'Expiré',
        };
    }
}
