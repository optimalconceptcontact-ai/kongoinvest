<?php

namespace App\Services\Compliance\Providers;

use App\Contracts\Compliance\AmlScreeningProviderContract;

class MockAmlScreeningProvider implements AmlScreeningProviderContract
{
    public function screen(string $fullName, string $country = 'CD', string $subjectType = 'individual'): array
    {
        $normalized = mb_strtolower(trim($fullName));

        // PEP simulation
        $isPep = str_contains($normalized, 'pep')
            || str_contains($normalized, 'ministre')
            || str_contains($normalized, 'depute')
            || str_contains($normalized, 'gouverneur')
            || str_contains($normalized, 'senateur');

        $pepMatch = [
            'has_match' => $isPep,
            'match_type' => $isPep ? 'direct_pep' : null,
            'match_score' => $isPep ? 0.96 : 0.00,
            'role_title' => $isPep ? 'Ancien membre du Gouvernement / Député National' : null,
            'jurisdiction' => $isPep ? $country : null,
            'matches' => $isPep ? [
                [
                    'name' => $fullName,
                    'category' => 'PEP National RDC',
                    'position' => 'Mandataire public / Responsable politique',
                    'source' => 'CENAREF / Registre des personnes politiquement exposées',
                ],
            ] : [],
        ];

        // Sanctions simulation
        $isSanctioned = str_contains($normalized, 'sanction')
            || str_contains($normalized, 'sdn')
            || str_contains($normalized, 'terror')
            || str_contains($normalized, 'bloque');

        $sanctionsMatch = [
            'has_match' => $isSanctioned,
            'match_score' => $isSanctioned ? 0.99 : 0.00,
            'matched_lists' => $isSanctioned ? ['UN_CONSOLIDATED', 'OFAC_SDN', 'CENAREF_RDC'] : [],
            'matches' => $isSanctioned ? [
                [
                    'entity_name' => $fullName,
                    'list_id' => 'UN-SANCTIONS-DRC-2024',
                    'program' => 'Sanctions financières ciblées RDC',
                    'asset_freeze' => true,
                ],
            ] : [],
        ];

        // Adverse media simulation
        $hasMediaHits = str_contains($normalized, 'fraud')
            || str_contains($normalized, 'corruption')
            || str_contains($normalized, 'scandal')
            || str_contains($normalized, 'detournement');

        $adverseMedia = [
            'has_hits' => $hasMediaHits,
            'hit_count' => $hasMediaHits ? 2 : 0,
            'hits' => $hasMediaHits ? [
                [
                    'title' => 'Enquête ouverte pour soupçons de malversations financières',
                    'source' => 'Presse économique de Kinshasa',
                    'date' => '2025-04-10',
                    'category' => 'Financial Crime / Corruption',
                    'snippet' => 'Le parquet financier a initié une information préliminaire concernant les flux de...',
                ],
            ] : [],
        ];

        return [
            'provider' => 'mock_aml_screening_engine',
            'pep' => $pepMatch,
            'sanctions' => $sanctionsMatch,
            'adverse_media' => $adverseMedia,
        ];
    }
}
