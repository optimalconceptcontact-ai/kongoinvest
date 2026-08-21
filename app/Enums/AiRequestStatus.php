<?php

namespace App\Enums;

enum AiRequestStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Processing => 'En cours',
            self::Completed => 'Terminée',
            self::Failed => 'Échouée',
        };
    }
}
