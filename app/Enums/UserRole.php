<?php

namespace App\Enums;

/**
 * Mirrors the role names seeded by RolesAndPermissionsSeeder. This enum
 * is a typed convenience for controllers/requests/React props — it does
 * NOT replace Spatie Permission as the source of truth for authorization.
 * A user's actual roles always come from $user->getRoleNames().
 *
 * Exactly three roles exist: Admin, Entrepreneur, Investor.
 * "Professional" is NOT a role — it is only a SubscriptionPlanKey (see
 * that enum). An early version of this scaffold incorrectly treated
 * Professional as a fourth role with its own registration option,
 * profile, and dashboard; that was a real design error, corrected here.
 * An entrepreneur or an investor may both subscribe to the Professional
 * plan — the plan is role-agnostic, same as Free.
 */
enum UserRole: string
{
    case Admin = 'admin';
    case Entrepreneur = 'entrepreneur';
    case Investor = 'investor';

    /** Roles selectable at self-registration (spec §9 — admin is never self-assigned). */
    public static function registrable(): array
    {
        return [self::Entrepreneur, self::Investor];
    }

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrateur',
            self::Entrepreneur => 'Entrepreneur',
            self::Investor => 'Investisseur',
        };
    }
}
