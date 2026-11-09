<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Congolese AML/CFT Legal Framework References
    |--------------------------------------------------------------------------
    |
    | - Loi n° 22/068 du 27 décembre 2022 portant lutte contre le blanchiment
    |   de capitaux et le financement du terrorisme et de la prolifération.
    | - Loi n° 25/048 du 1er juillet 2025 actualisant les obligations de vigilance.
    | - Acte uniforme OHADA révisé relatif au droit des sociétés commerciales (AUSCGIE).
    |
    */
    'legal' => [
        'jurisdiction' => 'CD',
        'primary_law' => 'Loi n° 22/068 du 27 décembre 2022',
        'amending_law' => 'Loi n° 25/048 du 1er juillet 2025',
        'retention_years' => 10, // Article 33: conservation 10 ans des dossiers d'identification et transactions
        'cash_payment_threshold_usd' => 10000, // Article 23: restriction des paiements en espèces (et non seuil universel de DOS)
    ],

    /*
    |--------------------------------------------------------------------------
    | Beneficial Ownership (UBO) Configuration
    |--------------------------------------------------------------------------
    |
    | Default statutory threshold is 25% of capital or voting rights.
    | Can be configured to 10% or lower for enhanced due diligence / high-risk entities.
    |
    */
    'ubo' => [
        'default_threshold_percentage' => 25.0,
        'high_risk_threshold_percentage' => 10.0,
    ],

    /*
    |--------------------------------------------------------------------------
    | AML Risk Assessment Pillars and Weights (Version 1.0)
    |--------------------------------------------------------------------------
    |
    | Approach based on Risk-Based Approach (RBA).
    | Total weight sums to 100%.
    |
    */
    'risk_assessment' => [
        'current_version' => '1.0.0',
        'weights' => [
            'client_nature' => 20,         // Type de personne, antécédents, structure
            'ubo_complexity' => 15,        // Niveaux d'interposition, juridictions offshore
            'geographic' => 15,            // FATF/GAFI grey/black lists, pays à haut risque
            'sectoral' => 15,              // Mines, extraction, négoce transfrontalier, etc.
            'product_channel' => 10,       // Distribution à distance, instruments complexes
            'source_of_funds' => 15,       // Justification économique et bancaire des fonds
            'transaction_behavior' => 10,  // Fractionnement, cohérence avec le profil
        ],
        'thresholds' => [
            'low' => 25,
            'medium' => 50,
            'high' => 75,
            'critical' => 90,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | External Provider Drivers
    |--------------------------------------------------------------------------
    |
    | Supported: 'mock', 'smile_id', 'sumsub', 'comply_advantage', 'world_check'
    |
    */
    'providers' => [
        'ocr' => env('COMPLIANCE_OCR_PROVIDER', 'mock'),
        'biometric' => env('COMPLIANCE_BIOMETRIC_PROVIDER', 'mock'),
        'aml_screening' => env('COMPLIANCE_AML_PROVIDER', 'mock'),
    ],

    /*
    |--------------------------------------------------------------------------
    | High Risk Geographies (FATF / GAFI & National Watchlists)
    |--------------------------------------------------------------------------
    */
    'high_risk_countries' => [
        'KP', // Corée du Nord (Black list)
        'IR', // Iran (Black list)
        'MM', // Myanmar (Black list)
        'SY', 'YE', 'SS', 'CD', // Grey lists & specific vigilance zones
    ],

    /*
    |--------------------------------------------------------------------------
    | High Risk Sectors
    |--------------------------------------------------------------------------
    */
    'high_risk_sectors' => [
        'mining_extractive',
        'precious_metals_stones',
        'arms_defense',
        'cross_border_cash_couriers',
        'unregulated_virtual_assets',
        'gambling_casinos',
    ],

    /*
    |--------------------------------------------------------------------------
    | Contract & Investment Compliance Gates
    |--------------------------------------------------------------------------
    |
    | Blocages durs, toujours actifs : dossier bloqué/suspendu, alerte
    | sanctions ou critique ouverte, KYC rejeté/expiré.
    |
    | Les listes ci-dessous ajoutent des checkpoints progressifs par
    | étape. `identity_checkpoint_stages` bloque dès qu'un dossier de
    | conformité a été commencé sans être approuvé ; `identity_required_stages`
    | exige une identité vérifiée même sans dossier entamé (à activer
    | en production via COMPLIANCE_IDENTITY_REQUIRED_STAGES).
    |
    | Étapes disponibles : subscription, data_room, workspace_opening,
    | contract_acceptance, signature, escrow_creation, escrow_funding,
    | escrow_release, closing.
    |
    */
    'gates' => [
        'identity_checkpoint_stages' => [
            'signature',
            'escrow_funding',
            'escrow_release',
            'closing',
        ],
        'identity_required_stages' => array_filter(array_map(
            'trim',
            explode(',', (string) env('COMPLIANCE_IDENTITY_REQUIRED_STAGES', '')),
        )),
        'kyb_checkpoint_stages' => [
            'closing',
        ],
        'kyb_required_stages' => array_filter(array_map(
            'trim',
            explode(',', (string) env('COMPLIANCE_KYB_REQUIRED_STAGES', '')),
        )),
        'ubo_required_stages' => array_filter(array_map(
            'trim',
            explode(',', (string) env('COMPLIANCE_UBO_REQUIRED_STAGES', '')),
        )),
        'ubo_checkpoint_stages' => [
            'closing',
        ],
        'source_of_funds_required_stages' => array_filter(array_map(
            'trim',
            explode(',', (string) env('COMPLIANCE_SOF_REQUIRED_STAGES', '')),
        )),
        'source_of_funds_threshold_minor' => (int) env('COMPLIANCE_SOF_THRESHOLD_MINOR', 1_000_000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Partner Bank Responsibilities & Endpoints
    |--------------------------------------------------------------------------
    */
    'partner_bank' => [
        'name' => env('PARTNER_BANK_NAME', 'Rawbank RDC / Equity BCDC'),
        'api_endpoint' => env('PARTNER_BANK_API_ENDPOINT', 'https://api.partnerbank.cd/v1/compliance'),
        'api_key' => env('PARTNER_BANK_API_KEY', 'sandbox_key'),
        'auto_sync' => false,
    ],
];
