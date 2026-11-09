<?php

namespace App\Services\Compliance;

use App\Enums\UboControlNature;
use App\Models\BeneficialOwner;
use App\Models\BusinessVerification;
use App\Models\OwnershipRelationship;

class BeneficialOwnershipService
{
    /**
     * Declare or update an individual beneficial owner (UBO).
     */
    public function declareBeneficialOwner(
        BusinessVerification $verification,
        array $data
    ): BeneficialOwner {
        $thresholdBps = (int) (config('compliance.ubo.default_threshold_percentage', 25.0) * 100);

        $directBps = (int) ($data['direct_ownership_bps'] ?? 0);
        $indirectBps = (int) ($data['indirect_ownership_bps'] ?? 0);
        $votingBps = (int) ($data['voting_rights_bps'] ?? $directBps + $indirectBps);
        $totalBps = $directBps + $indirectBps;

        $controlNature = $data['control_nature'] instanceof UboControlNature
            ? $data['control_nature']
            : UboControlNature::tryFrom($data['control_nature'] ?? '') ?? UboControlNature::DirectEquity;

        // Meets threshold if >= 25% or controls via voting / legal representation / pact
        $meetsThreshold = ($totalBps >= $thresholdBps)
            || ($votingBps >= $thresholdBps)
            || in_array($controlNature, [
                UboControlNature::LegalRepresentative,
                UboControlNature::ShareholdersAgreement,
                UboControlNature::DeFactoControl,
            ], true);

        return BeneficialOwner::create([
            'business_verification_id' => $verification->id,
            'user_id' => $data['user_id'] ?? null,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'postnom' => $data['postnom'] ?? null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'nationality' => strtoupper($data['nationality'] ?? 'CD'),
            'country_of_residence' => strtoupper($data['country_of_residence'] ?? 'CD'),
            'residential_address' => $data['residential_address'] ?? null,
            'is_pep' => (bool) ($data['is_pep'] ?? false),
            'pep_details' => $data['pep_details'] ?? null,
            'direct_ownership_bps' => $directBps,
            'indirect_ownership_bps' => $indirectBps,
            'voting_rights_bps' => $votingBps,
            'control_nature' => $controlNature,
            'meets_statutory_threshold' => $meetsThreshold,
            'justification_notes' => $data['justification_notes'] ?? null,
            'identity_document_file_path' => $data['identity_document_file_path'] ?? null,
            'is_verified' => false,
        ]);
    }

    /**
     * Record a link in the ownership chain (e.g. Person A -> Holding B -> Holding C -> Target).
     */
    public function recordOwnershipLink(
        BusinessVerification $verification,
        array $linkData
    ): OwnershipRelationship {
        return OwnershipRelationship::create([
            'business_verification_id' => $verification->id,
            'parent_entity_type' => $linkData['parent_entity_type'] ?? 'individual',
            'parent_name' => $linkData['parent_name'],
            'parent_registration_number' => $linkData['parent_registration_number'] ?? null,
            'parent_country' => strtoupper($linkData['parent_country'] ?? 'CD'),
            'child_entity_name' => $linkData['child_entity_name'],
            'child_registration_number' => $linkData['child_registration_number'] ?? null,
            'tier_level' => (int) ($linkData['tier_level'] ?? 1),
            'equity_percentage_bps' => (int) ($linkData['equity_percentage_bps'] ?? 0),
            'voting_percentage_bps' => (int) ($linkData['voting_percentage_bps'] ?? $linkData['equity_percentage_bps'] ?? 0),
            'control_mechanism' => $linkData['control_mechanism'] ?? UboControlNature::DirectEquity->value,
            'is_ultimate_owner' => (bool) ($linkData['is_ultimate_owner'] ?? false),
        ]);
    }

    /**
     * Calculate indirect ownership percentage across multiple tiers.
     * e.g. Person holds 60% of Holding B, Holding B holds 50% of Target => 60% * 50% = 30%.
     *
     * @param  array<int, int>  $tierPercentagesBps  Array of percentages in bps along the chain
     * @return int Effective percentage in bps
     */
    public function computeMultiTierPercentage(array $tierPercentagesBps): int
    {
        if (empty($tierPercentagesBps)) {
            return 0;
        }

        $cumulative = 1.0;
        foreach ($tierPercentagesBps as $bps) {
            $cumulative *= ($bps / 10000.0);
        }

        return (int) round($cumulative * 10000);
    }

    /**
     * Build graph visualization structure (nodes and edges) for UI rendering.
     *
     * @return array{
     *     nodes: array<int, array{id: string, label: string, type: string, percentage: float, is_pep: bool, tier: int}>,
     *     edges: array<int, array{from: string, to: string, label: string, equity_pct: float, voting_pct: float}>
     * }
     */
    public function buildOwnershipGraph(BusinessVerification $verification): array
    {
        $nodes = [];
        $edges = [];

        // Target Company node (Root)
        $targetId = 'target-'.$verification->id;
        $nodes[] = [
            'id' => $targetId,
            'label' => $verification->company_name,
            'type' => 'target_company',
            'percentage' => 100.0,
            'is_pep' => false,
            'tier' => 0,
        ];

        // Process relationships
        $relationships = $verification->ownershipRelationships()->get();
        foreach ($relationships as $rel) {
            $parentId = 'parent-'.md5($rel->parent_name);
            $childId = $rel->child_entity_name === $verification->company_name ? $targetId : 'child-'.md5($rel->child_entity_name);

            // Add parent node if not added
            if (! collect($nodes)->contains('id', $parentId)) {
                $nodes[] = [
                    'id' => $parentId,
                    'label' => $rel->parent_name,
                    'type' => $rel->parent_entity_type,
                    'percentage' => $rel->equityPercentage(),
                    'is_pep' => false,
                    'tier' => $rel->tier_level,
                ];
            }

            $edges[] = [
                'from' => $parentId,
                'to' => $childId,
                'label' => $rel->equityPercentage().'%',
                'equity_pct' => $rel->equityPercentage(),
                'voting_pct' => round($rel->voting_percentage_bps / 100, 2),
            ];
        }

        // Process Beneficial Owners
        $ubos = $verification->beneficialOwners()->get();
        foreach ($ubos as $ubo) {
            $uboId = 'ubo-'.$ubo->id;
            $nodes[] = [
                'id' => $uboId,
                'label' => $ubo->fullName(),
                'type' => 'individual_ubo',
                'percentage' => $ubo->totalEffectivePercentage(),
                'is_pep' => $ubo->is_pep,
                'tier' => 1,
            ];

            // If not already connected via edges, connect directly to target
            $alreadyConnected = collect($edges)->contains('from', $uboId);
            if (! $alreadyConnected) {
                $edges[] = [
                    'from' => $uboId,
                    'to' => $targetId,
                    'label' => $ubo->totalEffectivePercentage().'%',
                    'equity_pct' => $ubo->totalEffectivePercentage(),
                    'voting_pct' => round($ubo->voting_rights_bps / 100, 2),
                ];
            }
        }

        return [
            'nodes' => $nodes,
            'edges' => $edges,
        ];
    }
}
