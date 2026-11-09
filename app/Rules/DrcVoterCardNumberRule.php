<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Flexible DRC CENI Voter Card validation.
 * CENI Voter card formats have evolved across electoral cycles (2006, 2011, 2018, 2023),
 * typically consisting of 9 to 16 alphanumeric characters or digits, occasionally with dashes.
 * Strict universal format MUST NOT be imposed (Section 3).
 */
class DrcVoterCardNumberRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('Le numéro de carte d\'électeur doit être une chaîne de caractères.');

            return;
        }

        $cleaned = preg_replace('/[\s\-]/', '', $value);

        // Flexible CENI format: between 8 and 24 alphanumeric characters
        if (! preg_match('/^[A-Za-z0-9]{8,24}$/', $cleaned)) {
            $fail('Le format du numéro de carte d\'électeur RDC est invalide (8 à 24 caractères alphanumériques requis).');
        }
    }
}
