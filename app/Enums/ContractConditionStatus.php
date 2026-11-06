<?php

namespace App\Enums;

enum ContractConditionStatus: string
{
    case Pending = 'pending';
    case Fulfilled = 'fulfilled';
    case NotFulfilled = 'not_fulfilled';
    case Waived = 'waived';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Fulfilled => 'Conduite à terme',
            self::NotFulfilled => 'Non réalisée',
            self::Waived => 'Levée',
        };
    }
}
