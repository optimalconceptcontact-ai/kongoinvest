<?php

namespace App\Services\Compliance\Providers;

use App\Contracts\Compliance\PartnerBankExchangeContract;
use App\Models\ComplianceCase;

class MockPartnerBankExchangeProvider implements PartnerBankExchangeContract
{
    public function transmitComplianceDossier(ComplianceCase $case): array
    {
        $bankReference = 'BANK-REF-'.strtoupper(bin2hex(random_bytes(4)));

        return [
            'status' => 'transmitted',
            'bank_reference' => $bankReference,
            'transmitted_at' => now()->toIso8601String(),
            'message' => 'Dossier de conformité transmis à la banque partenaire avec succès pour revue indépendante.',
        ];
    }

    public function fetchBankDecision(string $bankReference): array
    {
        return [
            'bank_reference' => $bankReference,
            'bank_status' => 'approved_for_escrow',
            'conditions' => [
                'Virement bancaire exclusif depuis un compte ouvert au nom de l\'investisseur.',
                'Justificatif de domicile de moins de 3 mois validé.',
            ],
            'allows_deposit' => true,
            'allows_disbursement' => true,
            'comments' => 'Conforme aux exigences bancaires de la Banque Centrale du Congo (BCC).',
        ];
    }
}
