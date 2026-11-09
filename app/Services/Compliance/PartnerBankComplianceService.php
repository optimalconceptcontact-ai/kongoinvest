<?php

namespace App\Services\Compliance;

use App\Contracts\Compliance\PartnerBankExchangeContract;
use App\Models\ComplianceAuditLog;
use App\Models\ComplianceCase;
use App\Models\User;

class PartnerBankComplianceService
{
    public function __construct(
        private PartnerBankExchangeContract $exchangeProvider,
    ) {}

    /**
     * Get the authoritative responsibilities matrix (Section 12).
     *
     * @return array<string, array{actor: string, responsibilities: array<string>, legal_basis: string}>
     */
    public function getResponsibilitiesMatrix(): array
    {
        return [
            'platform' => [
                'actor' => 'InvestmentSolutions / KongoInvest',
                'responsibilities' => [
                    'Collecte numérique des dossiers KYC/KYB et documents d\'identification.',
                    'Vérification documentaire automatisée (OCR, intégrité, cohérence).',
                    'Screening AML/CFT initial (PEP, listes de sanctions, adverse media).',
                    'Cartographie et modélisation des bénéficiaires effectifs (UBO).',
                    'Évaluation préliminaire des risques AML et classification RBA.',
                    'Blocage des parties sous sanctions ou présentant un risque critique.',
                    'Transmission sécurisée des dossiers de conformité à la banque partenaire.',
                ],
                'legal_basis' => 'Obligations contractuelles de vigilance et diligence raisonnable de la plateforme intermédiaire.',
            ],
            'partner_bank' => [
                'actor' => 'Banque Partenaire RDC (Établissement de crédit agréé BCC)',
                'responsibilities' => [
                    'Contrôle KYC/AML indépendant et souverain.',
                    'Validation finale de l\'origine des fonds (Source of Funds).',
                    'Autorisation préalable d\'ouverture des comptes séquestres / escrow.',
                    'Autorisation du dépôt des fonds et encaissement.',
                    'Autorisation finale du décaissement vers l\'entreprise bénéficiaire.',
                    'Déclarations de soupçon (DOS) officielles auprès de la CENAREF au titre d\'assujetti légal.',
                ],
                'legal_basis' => 'Loi n° 22/068 (Art. 5, 23, 30), Instructions BCC relatives aux établissements de crédit.',
            ],
            'investor' => [
                'actor' => 'Investisseur (Personne physique ou morale)',
                'responsibilities' => [
                    'Fourniture de pièces d\'identité authentiques et en cours de validité.',
                    'Déclaration sincère du statut PEP et des ayants droit économiques.',
                    'Justification documentée de la provenance des fonds (SOF) et du patrimoine (SOW).',
                    'Exécution des virements bancaires exclusivement depuis un compte à son nom.',
                ],
                'legal_basis' => 'Obligation déclarative de sincérité et conditions générales d\'investissement.',
            ],
            'target_company' => [
                'actor' => 'Société Cible / Porteur de projet',
                'responsibilities' => [
                    'Fourniture des statuts, RCCM, NIF et autorisations d\'exploitation.',
                    'Divulgation exhaustive de la cap table et de l\'organigramme de détention.',
                    'Identification de tous les bénéficiaires effectifs détenant >= 25 % ou le contrôle effectif.',
                    'Affectation conforme des fonds reçus conformément au contrat d\'investissement.',
                ],
                'legal_basis' => 'Droit des sociétés commerciales OHADA et obligations déclaratives.',
            ],
        ];
    }

    /**
     * Transmit compliance package to the partner bank.
     */
    public function transmitToBank(ComplianceCase $case, User $officer): array
    {
        $transmissionResult = $this->exchangeProvider->transmitComplianceDossier($case);

        // Record case action
        $case->actions()->create([
            'actor_user_id' => $officer->id,
            'action_type' => 'partner_bank_transmission',
            'comment' => "Dossier transmis à la banque partenaire. Référence : {$transmissionResult['bank_reference']}",
            'payload' => $transmissionResult,
        ]);

        ComplianceAuditLog::create([
            'compliance_case_id' => $case->id,
            'actor_user_id' => $officer->id,
            'event' => 'compliance.transmitted_to_bank',
            'subject_type' => $case::class,
            'subject_id' => $case->id,
            'context' => [
                'bank_reference' => $transmissionResult['bank_reference'] ?? null,
                'bank_status' => $transmissionResult['bank_status'] ?? null,
                'actor' => $officer->name,
            ],
            'legal_retention_required' => true,
            'retention_until' => now()->addYears(config('compliance.legal.retention_years', 10)),
        ]);

        return $transmissionResult;
    }

    /**
     * Synchronize and record independent bank decision.
     */
    public function syncBankDecision(ComplianceCase $case, string $bankReference): array
    {
        $decision = $this->exchangeProvider->fetchBankDecision($bankReference);

        $case->actions()->create([
            'action_type' => 'partner_bank_decision_received',
            'comment' => "Décision banque partenaire reçue : {$decision['bank_status']}",
            'payload' => $decision,
        ]);

        ComplianceAuditLog::create([
            'compliance_case_id' => $case->id,
            'actor_user_id' => null,
            'event' => 'compliance.bank_decision_received',
            'subject_type' => $case::class,
            'subject_id' => $case->id,
            'context' => $decision,
            'legal_retention_required' => true,
            'retention_until' => now()->addYears(config('compliance.legal.retention_years', 10)),
        ]);

        return $decision;
    }
}
