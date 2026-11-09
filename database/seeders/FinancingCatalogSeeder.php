<?php

namespace Database\Seeders;

use App\Models\FinancialInstrument;
use App\Models\FinancingChannel;
use App\Models\FinancingSource;
use App\Models\FinancingType;
use App\Models\InstrumentJurisdictionRule;
use App\Models\Stage;
use Illuminate\Database\Seeder;

class FinancingCatalogSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Financing Types
        $typesData = [
            [
                'code' => 'equity',
                'name' => 'Prise de participation (Equity)',
                'slug' => 'equity',
                'description' => 'Entrée au capital sous forme d\'actions ordinaires, de préférence ou parts sociales.',
                'icon' => 'RiPieChartLine',
                'color' => '#2563EB',
                'sort_order' => 1,
            ],
            [
                'code' => 'debt',
                'name' => 'Financement par dette (Debt)',
                'slug' => 'debt',
                'description' => 'Prêts bancaires, prêts d\'honneur, obligations et crédits court ou moyen terme avec obligation de remboursement.',
                'icon' => 'RiBankLine',
                'color' => '#059669',
                'sort_order' => 2,
            ],
            [
                'code' => 'hybrid',
                'name' => 'Financement hybride (Quasi-equity)',
                'slug' => 'hybrid',
                'description' => 'Instruments convertibles, SAFE, dette mezzanine et prêts participatifs combinant caractéristiques de dette et de capital.',
                'icon' => 'RiExchangeFundsLine',
                'color' => '#D97706',
                'sort_order' => 3,
            ],
            [
                'code' => 'grant',
                'name' => 'Subvention (Grant)',
                'slug' => 'grant',
                'description' => 'Financements non remboursables d\'amorçage, d\'innovation, de recherche ou d\'impact.',
                'icon' => 'RiGiftLine',
                'color' => '#7C3AED',
                'sort_order' => 4,
            ],
            [
                'code' => 'revenue_share',
                'name' => 'Partage de revenus (Revenue-Based)',
                'slug' => 'revenue-share',
                'description' => 'Financement indexé sur le chiffre d\'affaires sans dilution de capital (RBF, redevances/royalties).',
                'icon' => 'RiPercentLine',
                'color' => '#DC2626',
                'sort_order' => 5,
            ],
            [
                'code' => 'alternative',
                'name' => 'Financements alternatifs',
                'slug' => 'alternative',
                'description' => 'Mécanismes émergents et combinés (Crowdfunding, Leasing, Affacturage, Blended Finance, Bootstrapping).',
                'icon' => 'RiApps2Line',
                'color' => '#0891B2',
                'sort_order' => 6,
            ],
        ];

        $types = [];
        foreach ($typesData as $data) {
            $types[$data['code']] = FinancingType::updateOrCreate(
                ['code' => $data['code']],
                array_merge($data, ['is_active' => true])
            );
        }

        // 2. Financial Instruments
        $instrumentsData = [
            // Equity
            [
                'code' => 'common_shares',
                'name' => 'Actions ordinaires',
                'slug' => 'actions-ordinaires',
                'description' => 'Titres de capital standard conférant un droit de vote ordinaire et un dividende proportionnel.',
                'instrument_category' => 'equity',
                'repayment_required' => false,
                'ownership_dilution' => true,
                'remuneration_type' => 'variable',
                'conversion_possible' => false,
                'collateral_possible' => false,
                'sort_order' => 10,
                'primary_type' => 'equity',
                'also_in_types' => [],
            ],
            [
                'code' => 'preferred_shares',
                'name' => 'Actions de préférence',
                'slug' => 'actions-de-preference',
                'description' => 'Titres avec avantages pécuniaires (dividende prioritaire) ou extra-patrimoniaux.',
                'instrument_category' => 'equity',
                'repayment_required' => false,
                'ownership_dilution' => true,
                'remuneration_type' => 'variable',
                'conversion_possible' => true,
                'collateral_possible' => false,
                'sort_order' => 11,
                'primary_type' => 'equity',
                'also_in_types' => [],
            ],
            [
                'code' => 'equity_interests',
                'name' => 'Parts sociales (SARL)',
                'slug' => 'parts-sociales',
                'description' => 'Titres de capital non négociables réservés aux sociétés à responsabilité limitée OHADA.',
                'instrument_category' => 'equity',
                'repayment_required' => false,
                'ownership_dilution' => true,
                'remuneration_type' => 'variable',
                'conversion_possible' => false,
                'collateral_possible' => false,
                'sort_order' => 12,
                'primary_type' => 'equity',
                'also_in_types' => [],
            ],
            [
                'code' => 'special_voting_shares',
                'name' => 'Actions à droit de vote spécifique',
                'slug' => 'actions-droit-vote-specifique',
                'description' => 'Actions aménageant la gouvernance (droit de vote double ou contingent).',
                'instrument_category' => 'equity',
                'repayment_required' => false,
                'ownership_dilution' => true,
                'remuneration_type' => 'variable',
                'conversion_possible' => false,
                'collateral_possible' => false,
                'sort_order' => 13,
                'primary_type' => 'equity',
                'also_in_types' => [],
            ],
            [
                'code' => 'strategic_equity',
                'name' => 'Investissement stratégique au capital',
                'slug' => 'investissement-strategique-capital',
                'description' => 'Prise de participation minoritaire ou conjointe avec partenaire industriel.',
                'instrument_category' => 'equity',
                'repayment_required' => false,
                'ownership_dilution' => true,
                'remuneration_type' => 'variable',
                'conversion_possible' => false,
                'collateral_possible' => false,
                'sort_order' => 14,
                'primary_type' => 'equity',
                'also_in_types' => [],
            ],

            // Debt
            [
                'code' => 'bank_loan',
                'name' => 'Prêt bancaire commercial',
                'slug' => 'pret-bancaire',
                'description' => 'Financement bancaire traditionnel avec échéancier d\'intérêts et de principal.',
                'instrument_category' => 'debt',
                'repayment_required' => true,
                'ownership_dilution' => false,
                'remuneration_type' => 'fixed',
                'conversion_possible' => false,
                'collateral_possible' => true,
                'sort_order' => 20,
                'primary_type' => 'debt',
                'also_in_types' => [],
            ],
            [
                'code' => 'term_loan',
                'name' => 'Prêt à terme (Term Loan)',
                'slug' => 'pret-a-terme',
                'description' => 'Prêt à moyen ou long terme structuré avec calendrier de remboursement défini.',
                'instrument_category' => 'debt',
                'repayment_required' => true,
                'ownership_dilution' => false,
                'remuneration_type' => 'fixed',
                'conversion_possible' => false,
                'collateral_possible' => true,
                'sort_order' => 21,
                'primary_type' => 'debt',
                'also_in_types' => [],
            ],
            [
                'code' => 'working_capital_loan',
                'name' => 'Crédit de fonds de roulement / Trésorerie',
                'slug' => 'credit-tresorerie-bfr',
                'description' => 'Facilité de crédit court terme pour financer le cycle d\'exploitation.',
                'instrument_category' => 'debt',
                'repayment_required' => true,
                'ownership_dilution' => false,
                'remuneration_type' => 'fixed',
                'conversion_possible' => false,
                'collateral_possible' => true,
                'sort_order' => 22,
                'primary_type' => 'debt',
                'also_in_types' => [],
            ],
            [
                'code' => 'secured_loan',
                'name' => 'Prêt garanti avec sûretés',
                'slug' => 'pret-garanti',
                'description' => 'Prêt adossé à un nantissement, hypothèque ou cautionnement personnel/solidaire.',
                'instrument_category' => 'debt',
                'repayment_required' => true,
                'ownership_dilution' => false,
                'remuneration_type' => 'fixed',
                'conversion_possible' => false,
                'collateral_possible' => true,
                'sort_order' => 23,
                'primary_type' => 'debt',
                'also_in_types' => [],
            ],
            [
                'code' => 'unsecured_loan',
                'name' => 'Prêt non garanti / Prêt d\'honneur',
                'slug' => 'pret-honneur',
                'description' => 'Prêt consenti sans sûreté réelle, souvent à taux zéro ou bonifié pour amorçage.',
                'instrument_category' => 'debt',
                'repayment_required' => true,
                'ownership_dilution' => false,
                'remuneration_type' => 'none',
                'conversion_possible' => false,
                'collateral_possible' => false,
                'sort_order' => 24,
                'primary_type' => 'debt',
                'also_in_types' => [],
            ],
            [
                'code' => 'trade_credit',
                'name' => 'Crédit commercial / fournisseur',
                'slug' => 'credit-commercial',
                'description' => 'Délais de paiement accordés par des fournisseurs ou bailleurs pour l\'activité.',
                'instrument_category' => 'debt',
                'repayment_required' => true,
                'ownership_dilution' => false,
                'remuneration_type' => 'none',
                'conversion_possible' => false,
                'collateral_possible' => false,
                'sort_order' => 25,
                'primary_type' => 'debt',
                'also_in_types' => [],
            ],
            [
                'code' => 'corporate_bond',
                'name' => 'Obligation d\'entreprise (Corporate Bond)',
                'slug' => 'obligation-entreprise',
                'description' => 'Titre de créance négociable émis par une entreprise auprès d\'investisseurs.',
                'instrument_category' => 'debt',
                'repayment_required' => true,
                'ownership_dilution' => false,
                'remuneration_type' => 'fixed',
                'conversion_possible' => false,
                'collateral_possible' => true,
                'sort_order' => 26,
                'primary_type' => 'debt',
                'also_in_types' => [],
            ],
            [
                'code' => 'promissory_note',
                'name' => 'Billet à ordre / Effet de commerce',
                'slug' => 'billet-a-ordre',
                'description' => 'Engagement écrit unilatéral de payer une somme déterminée à échéance.',
                'instrument_category' => 'debt',
                'repayment_required' => true,
                'ownership_dilution' => false,
                'remuneration_type' => 'fixed',
                'conversion_possible' => false,
                'collateral_possible' => false,
                'sort_order' => 27,
                'primary_type' => 'debt',
                'also_in_types' => [],
            ],

            // Hybrid
            [
                'code' => 'convertible_bond',
                'name' => 'Obligation convertible (OCA)',
                'slug' => 'obligation-convertible',
                'description' => 'Titre de dette offrant au créancier la faculté de convertir sa créance en actions.',
                'instrument_category' => 'hybrid',
                'repayment_required' => true,
                'ownership_dilution' => true,
                'remuneration_type' => 'fixed',
                'conversion_possible' => true,
                'collateral_possible' => false,
                'sort_order' => 30,
                'primary_type' => 'hybrid',
                'also_in_types' => ['debt'],
            ],
            [
                'code' => 'convertible_loan',
                'name' => 'Prêt convertible (Convertible Note)',
                'slug' => 'pret-convertible',
                'description' => 'Prêt avec clause de conversion automatique ou optionnelle lors du prochain tour.',
                'instrument_category' => 'hybrid',
                'repayment_required' => true,
                'ownership_dilution' => true,
                'remuneration_type' => 'fixed',
                'conversion_possible' => true,
                'collateral_possible' => false,
                'sort_order' => 31,
                'primary_type' => 'hybrid',
                'also_in_types' => ['debt'],
            ],
            [
                'code' => 'safe',
                'name' => 'SAFE (Simple Agreement for Future Equity)',
                'slug' => 'safe',
                'description' => 'Contrat d\'investissement rapide prévoyant l\'attribution d\'actions futures selon cap et décote.',
                'instrument_category' => 'hybrid',
                'repayment_required' => false,
                'ownership_dilution' => true,
                'remuneration_type' => 'none',
                'conversion_possible' => true,
                'collateral_possible' => false,
                'sort_order' => 32,
                'primary_type' => 'hybrid',
                'also_in_types' => ['equity'],
            ],
            [
                'code' => 'mezzanine_debt',
                'name' => 'Dette mezzanine',
                'slug' => 'dette-mezzanine',
                'description' => 'Dette subordonnée combinée à un intéressement en capital (warrants / BSA).',
                'instrument_category' => 'hybrid',
                'repayment_required' => true,
                'ownership_dilution' => true,
                'remuneration_type' => 'variable',
                'conversion_possible' => true,
                'collateral_possible' => false,
                'sort_order' => 33,
                'primary_type' => 'hybrid',
                'also_in_types' => ['debt'],
            ],
            [
                'code' => 'participating_loan',
                'name' => 'Prêt participatif',
                'slug' => 'pret-participatif',
                'description' => 'Créance de dernier rang dont la rémunération comporte une part fixe et une part variable sur les résultats.',
                'instrument_category' => 'hybrid',
                'repayment_required' => true,
                'ownership_dilution' => false,
                'remuneration_type' => 'profit_share',
                'conversion_possible' => false,
                'collateral_possible' => false,
                'sort_order' => 34,
                'primary_type' => 'hybrid',
                'also_in_types' => ['debt'],
            ],
            [
                'code' => 'warrant_linked_bond',
                'name' => 'Obligation à bons de souscription (OBSA)',
                'slug' => 'obsa-bons-souscription',
                'description' => 'Obligation émise avec bon de souscription d\'actions négociable séparément.',
                'instrument_category' => 'hybrid',
                'repayment_required' => true,
                'ownership_dilution' => true,
                'remuneration_type' => 'fixed',
                'conversion_possible' => true,
                'collateral_possible' => false,
                'sort_order' => 35,
                'primary_type' => 'hybrid',
                'also_in_types' => ['debt', 'equity'],
            ],

            // Grant
            [
                'code' => 'investment_grant',
                'name' => 'Subvention d\'investissement',
                'slug' => 'subvention-investissement',
                'description' => 'Aide publique ou de bailleur finançant des actifs productifs et immobilisations.',
                'instrument_category' => 'grant',
                'repayment_required' => false,
                'ownership_dilution' => false,
                'remuneration_type' => 'none',
                'conversion_possible' => false,
                'collateral_possible' => false,
                'sort_order' => 40,
                'primary_type' => 'grant',
                'also_in_types' => [],
            ],
            [
                'code' => 'operating_grant',
                'name' => 'Subvention d\'exploitation',
                'slug' => 'subvention-exploitation',
                'description' => 'Allocation pour compenser des charges d\'exploitation ou financer des salaires/formations.',
                'instrument_category' => 'grant',
                'repayment_required' => false,
                'ownership_dilution' => false,
                'remuneration_type' => 'none',
                'conversion_possible' => false,
                'collateral_possible' => false,
                'sort_order' => 41,
                'primary_type' => 'grant',
                'also_in_types' => [],
            ],
            [
                'code' => 'innovation_grant',
                'name' => 'Subvention d\'innovation / R&D',
                'slug' => 'subvention-innovation',
                'description' => 'Concours financier pour projets technologiques ou prototypage innovant.',
                'instrument_category' => 'grant',
                'repayment_required' => false,
                'ownership_dilution' => false,
                'remuneration_type' => 'none',
                'conversion_possible' => false,
                'collateral_possible' => false,
                'sort_order' => 42,
                'primary_type' => 'grant',
                'also_in_types' => [],
            ],
            [
                'code' => 'results_based_grant',
                'name' => 'Subvention basée sur les résultats',
                'slug' => 'subvention-basee-resultats',
                'description' => 'Déblocage de tranches conditionné à l\'atteinte d\'indicateurs d\'impact vérifiés.',
                'instrument_category' => 'grant',
                'repayment_required' => false,
                'ownership_dilution' => false,
                'remuneration_type' => 'none',
                'conversion_possible' => false,
                'collateral_possible' => false,
                'sort_order' => 43,
                'primary_type' => 'grant',
                'also_in_types' => [],
            ],
            [
                'code' => 'conditional_grant',
                'name' => 'Avance remboursable / Subvention conditionnelle',
                'slug' => 'avance-remboursable-conditionnelle',
                'description' => 'Aide remboursable uniquement en cas de succès commercial du projet.',
                'instrument_category' => 'grant',
                'repayment_required' => true,
                'ownership_dilution' => false,
                'remuneration_type' => 'none',
                'conversion_possible' => false,
                'collateral_possible' => false,
                'sort_order' => 44,
                'primary_type' => 'grant',
                'also_in_types' => ['debt'],
            ],

            // Revenue-Based Financing
            [
                'code' => 'rbf_agreement',
                'name' => 'Revenue-Based Financing Agreement (RBF)',
                'slug' => 'revenue-based-financing',
                'description' => 'Avance de fonds remboursée par un pourcentage fixe des revenus mensuels jusqu\'au cap convenu.',
                'instrument_category' => 'revenue_share',
                'repayment_required' => true,
                'ownership_dilution' => false,
                'remuneration_type' => 'variable',
                'conversion_possible' => false,
                'collateral_possible' => false,
                'sort_order' => 50,
                'primary_type' => 'revenue_share',
                'also_in_types' => [],
            ],
            [
                'code' => 'royalty_financing',
                'name' => 'Contrat de redevance / Royalties',
                'slug' => 'contrat-redevance-royalties',
                'description' => 'Rémunération de l\'investisseur par redevance périodique sur les ventes de produits/services.',
                'instrument_category' => 'revenue_share',
                'repayment_required' => true,
                'ownership_dilution' => false,
                'remuneration_type' => 'variable',
                'conversion_possible' => false,
                'collateral_possible' => false,
                'sort_order' => 51,
                'primary_type' => 'revenue_share',
                'also_in_types' => [],
            ],
            [
                'code' => 'sales_based_repayment',
                'name' => 'Remboursement indexé sur les ventes',
                'slug' => 'remboursement-indexe-ventes',
                'description' => 'Remboursement de dette modulable directement selon la saisonnalité et les encaissements réels.',
                'instrument_category' => 'revenue_share',
                'repayment_required' => true,
                'ownership_dilution' => false,
                'remuneration_type' => 'variable',
                'conversion_possible' => false,
                'collateral_possible' => false,
                'sort_order' => 52,
                'primary_type' => 'revenue_share',
                'also_in_types' => ['debt'],
            ],

            // Alternative (Regroupement & Mécanismes)
            [
                'code' => 'bootstrapping',
                'name' => 'Auto-financement (Bootstrapping / Love Money)',
                'slug' => 'auto-financement-bootstrapping',
                'description' => 'Fonds propres apportés directement par les fondateurs, la famille et les proches.',
                'instrument_category' => 'alternative',
                'repayment_required' => false,
                'ownership_dilution' => false,
                'remuneration_type' => 'none',
                'conversion_possible' => false,
                'collateral_possible' => false,
                'sort_order' => 60,
                'primary_type' => 'alternative',
                'also_in_types' => ['equity'],
            ],
            [
                'code' => 'donation_crowdfunding',
                'name' => 'Crowdfunding par don',
                'slug' => 'crowdfunding-don',
                'description' => 'Financement participatif sans contrepartie financière, idéal pour amorçage et impact social.',
                'instrument_category' => 'alternative',
                'repayment_required' => false,
                'ownership_dilution' => false,
                'remuneration_type' => 'none',
                'conversion_possible' => false,
                'collateral_possible' => false,
                'sort_order' => 61,
                'primary_type' => 'alternative',
                'also_in_types' => ['grant'],
            ],
            [
                'code' => 'reward_crowdfunding',
                'name' => 'Crowdfunding avec contrepartie (Pré-ventes)',
                'slug' => 'crowdfunding-contrepartie',
                'description' => 'Campagne participative où les contributeurs reçoivent un produit, service ou reconnaissance symbolique.',
                'instrument_category' => 'alternative',
                'repayment_required' => false,
                'ownership_dilution' => false,
                'remuneration_type' => 'none',
                'conversion_possible' => false,
                'collateral_possible' => false,
                'sort_order' => 62,
                'primary_type' => 'alternative',
                'also_in_types' => [],
            ],
            [
                'code' => 'crowdlending',
                'name' => 'Crowdlending (Prêt participatif)',
                'slug' => 'crowdlending-pret-participatif',
                'description' => 'Emprunt rémunéré collecté auprès d\'une communauté de prêteurs via plateforme agréée.',
                'instrument_category' => 'alternative',
                'repayment_required' => true,
                'ownership_dilution' => false,
                'remuneration_type' => 'fixed',
                'conversion_possible' => false,
                'collateral_possible' => false,
                'sort_order' => 63,
                'primary_type' => 'alternative',
                'also_in_types' => ['debt'],
            ],
            [
                'code' => 'equity_crowdfunding',
                'name' => 'Equity Crowdfunding (Capital participatif)',
                'slug' => 'equity-crowdfunding',
                'description' => 'Souscription de parts ou d\'actions par un collectif d\'investisseurs particuliers et avisés.',
                'instrument_category' => 'alternative',
                'repayment_required' => false,
                'ownership_dilution' => true,
                'remuneration_type' => 'variable',
                'conversion_possible' => false,
                'collateral_possible' => false,
                'sort_order' => 64,
                'primary_type' => 'alternative',
                'also_in_types' => ['equity'],
            ],
            [
                'code' => 'leasing',
                'name' => 'Crédit-bail / Leasing mobilier & matériel',
                'slug' => 'credit-bail-leasing',
                'description' => 'Location d\'équipements productifs assortie d\'une option d\'achat à valeur résiduelle.',
                'instrument_category' => 'alternative',
                'repayment_required' => true,
                'ownership_dilution' => false,
                'remuneration_type' => 'fixed',
                'conversion_possible' => false,
                'collateral_possible' => true,
                'sort_order' => 65,
                'primary_type' => 'alternative',
                'also_in_types' => ['debt'],
            ],
            [
                'code' => 'factoring',
                'name' => 'Affacturage (Factoring)',
                'slug' => 'affacturage-factoring',
                'description' => 'Cession de créances commerciales à un factor pour obtenir immédiatement des liquidités.',
                'instrument_category' => 'alternative',
                'repayment_required' => true,
                'ownership_dilution' => false,
                'remuneration_type' => 'fixed',
                'conversion_possible' => false,
                'collateral_possible' => true,
                'sort_order' => 66,
                'primary_type' => 'alternative',
                'also_in_types' => ['debt'],
            ],
            [
                'code' => 'blended_finance',
                'name' => 'Financement mixte structuré (Blended Finance)',
                'slug' => 'blended-finance',
                'description' => 'Combinaison de capital concessionnel (subvention/garantie) et de capitaux commerciaux privés.',
                'instrument_category' => 'alternative',
                'repayment_required' => true,
                'ownership_dilution' => true,
                'remuneration_type' => 'variable',
                'conversion_possible' => true,
                'collateral_possible' => true,
                'sort_order' => 67,
                'primary_type' => 'alternative',
                'also_in_types' => ['equity', 'debt', 'grant'],
            ],
        ];

        $instruments = [];
        foreach ($instrumentsData as $data) {
            $primaryType = $data['primary_type'];
            $alsoInTypes = $data['also_in_types'];
            unset($data['primary_type'], $data['also_in_types']);

            $instrument = FinancialInstrument::updateOrCreate(
                ['code' => $data['code']],
                array_merge($data, ['is_active' => true])
            );
            $instruments[$data['code']] = $instrument;

            // Associate to primary type
            if (isset($types[$primaryType])) {
                $instrument->financingTypes()->syncWithoutDetaching([
                    $types[$primaryType]->id => ['is_primary' => true, 'sort_order' => 0],
                ]);
            }

            // Associate to secondary types if any
            foreach ($alsoInTypes as $secType) {
                if (isset($types[$secType])) {
                    $instrument->financingTypes()->syncWithoutDetaching([
                        $types[$secType]->id => ['is_primary' => false, 'sort_order' => 10],
                    ]);
                }
            }
        }

        // 3. Financing Sources
        $sourcesData = [
            ['code' => 'founder_promoter', 'name' => 'Fondateur / Promoteur', 'slug' => 'fondateur-promoteur', 'source_category' => 'private_individual', 'sort_order' => 1],
            ['code' => 'family_friends', 'name' => 'Famille et Proches (Love Money)', 'slug' => 'famille-proches', 'source_category' => 'private_individual', 'sort_order' => 2],
            ['code' => 'business_angel', 'name' => 'Business Angel', 'slug' => 'business-angel', 'source_category' => 'private_individual', 'sort_order' => 3],
            ['code' => 'venture_capital', 'name' => 'Fonds de capital-risque (VC)', 'slug' => 'venture-capital', 'source_category' => 'private_institutional', 'sort_order' => 4],
            ['code' => 'private_equity', 'name' => 'Fonds de capital-développement (PE)', 'slug' => 'private-equity', 'source_category' => 'private_institutional', 'sort_order' => 5],
            ['code' => 'commercial_bank', 'name' => 'Banque commerciale', 'slug' => 'banque-commerciale', 'source_category' => 'private_institutional', 'sort_order' => 6],
            ['code' => 'microfinance_institution', 'name' => 'Institution de microfinance (IMF)', 'slug' => 'microfinance', 'source_category' => 'private_institutional', 'sort_order' => 7],
            ['code' => 'government_institution', 'name' => 'Institution étatique / Fonds public', 'slug' => 'institution-publique', 'source_category' => 'public_national', 'sort_order' => 8],
            ['code' => 'development_finance_institution', 'name' => 'Institution de financement du développement (DFI)', 'slug' => 'dfi-developpement', 'source_category' => 'international_dfi', 'sort_order' => 9],
            ['code' => 'international_organization', 'name' => 'Organisation internationale / Bailleur', 'slug' => 'organisation-internationale', 'source_category' => 'international_dfi', 'sort_order' => 10],
            ['code' => 'foundation', 'name' => 'Fondation d\'entreprise ou philanthropique', 'slug' => 'fondation', 'source_category' => 'private_institutional', 'sort_order' => 11],
            ['code' => 'corporate_investor', 'name' => 'Investisseur corporatif stratégique', 'slug' => 'investisseur-corporatif', 'source_category' => 'private_institutional', 'sort_order' => 12],
            ['code' => 'crowdfunding_platform', 'name' => 'Collectif d\'investisseurs (Crowd)', 'slug' => 'crowdfunding-community', 'source_category' => 'market', 'sort_order' => 13],
        ];

        foreach ($sourcesData as $s) {
            FinancingSource::updateOrCreate(
                ['code' => $s['code']],
                array_merge($s, ['is_active' => true])
            );
        }

        // 4. Financing Channels
        $channelsData = [
            ['code' => 'direct_investment', 'name' => 'Investissement direct', 'slug' => 'investissement-direct', 'requires_intermediary' => false, 'sort_order' => 1],
            ['code' => 'bank_financing', 'name' => 'Financement bancaire', 'slug' => 'financement-bancaire', 'requires_intermediary' => true, 'sort_order' => 2],
            ['code' => 'crowdfunding_platform', 'name' => 'Plateforme de financement participatif', 'slug' => 'plateforme-crowdfunding', 'requires_intermediary' => true, 'sort_order' => 3],
            ['code' => 'institutional_funding', 'name' => 'Guichet institutionnel / Bailleurs', 'slug' => 'guichet-institutionnel', 'requires_intermediary' => true, 'sort_order' => 4],
            ['code' => 'public_funding_program', 'name' => 'Programme public / Concours d\'État', 'slug' => 'programme-public', 'requires_intermediary' => false, 'sort_order' => 5],
            ['code' => 'strategic_partnership', 'name' => 'Partenariat stratégique bilatéral', 'slug' => 'partenariat-strategique', 'requires_intermediary' => false, 'sort_order' => 6],
            ['code' => 'capital_markets', 'name' => 'Marchés financiers de capitaux', 'slug' => 'marches-capitaux', 'requires_intermediary' => true, 'sort_order' => 7],
        ];

        foreach ($channelsData as $c) {
            FinancingChannel::updateOrCreate(
                ['code' => $c['code']],
                array_merge($c, ['is_active' => true])
            );
        }

        // 5. Jurisdiction Rules (DRC / OHADA specifics)
        $drcSpecificRules = [
            'safe' => [
                'legal_status' => 'restricted',
                'requires_incorporation' => true,
                'regulatory_notes' => 'Non codifié à l\'identique en droit OHADA. Requiert une qualification en convention de souscription d\'actions futures avec clause suspensive d\'augmentation de capital.',
            ],
            'corporate_bond' => [
                'legal_status' => 'restricted',
                'requires_incorporation' => true,
                'minimum_capital_minor' => 1000000000, // 10,000,000 CDF / OHADA SA
                'regulatory_notes' => 'Réservé aux Sociétés Anonymes (SA) ayant au moins 2 années d\'existence avec bilans certifiés par un commissaire aux comptes.',
            ],
            'crowdlending' => [
                'legal_status' => 'restricted',
                'requires_incorporation' => true,
                'regulatory_notes' => 'Encadré par la réglementation BCC sur les opérations de crédit. Requiert un adossement obligatoire à une banque ou IMF agréée.',
            ],
            'bootstrapping' => [
                'legal_status' => 'approved',
                'requires_incorporation' => false,
                'regulatory_notes' => 'Accessible aux personnes physiques porteuses de projets au stade idée sans immatriculation préalable.',
            ],
            'donation_crowdfunding' => [
                'legal_status' => 'approved',
                'requires_incorporation' => false,
                'regulatory_notes' => 'Accessible aux projets associatifs ou porteurs individuels au stade idée.',
            ],
            'reward_crowdfunding' => [
                'legal_status' => 'approved',
                'requires_incorporation' => false,
                'regulatory_notes' => 'Autorisé pour pré-ventes ou campagnes participatives sans émission de titres financiers.',
            ],
            'investment_grant' => [
                'legal_status' => 'approved',
                'requires_incorporation' => false,
                'regulatory_notes' => 'Éligible aux porteurs de projet dès la phase d\'idéation selon les critères du bailleur.',
            ],
            'innovation_grant' => [
                'legal_status' => 'approved',
                'requires_incorporation' => false,
                'regulatory_notes' => 'Concours et prix d\'innovation ouverts aux projets non immatriculés.',
            ],
        ];

        foreach ($instruments as $code => $inst) {
            $isSpecial = isset($drcSpecificRules[$code]);
            $status = $isSpecial ? $drcSpecificRules[$code]['legal_status'] : 'approved';
            $requiresIncorp = $isSpecial ? $drcSpecificRules[$code]['requires_incorporation'] : true;
            $notes = $isSpecial ? $drcSpecificRules[$code]['regulatory_notes'] : 'Conforme au droit OHADA / législation RDC standard.';

            InstrumentJurisdictionRule::updateOrCreate(
                ['financial_instrument_id' => $inst->id, 'jurisdiction_code' => 'CD'],
                [
                    'legal_status' => $status,
                    'requires_incorporation' => $requiresIncorp,
                    'minimum_capital_minor' => $isSpecial && isset($drcSpecificRules[$code]['minimum_capital_minor']) ? $drcSpecificRules[$code]['minimum_capital_minor'] : null,
                    'regulatory_notes' => $notes,
                ]
            );
        }

        // 6. Stage Compatibilities (7 stages)
        $stages = Stage::all()->keyBy('key');
        if ($stages->isNotEmpty()) {
            foreach ($instruments as $code => $inst) {
                // If it doesn't require incorporation, it can be used at 'idea' stage
                $requiresIncorp = isset($drcSpecificRules[$code]) ? $drcSpecificRules[$code]['requires_incorporation'] : true;

                $compatibleStageKeys = [];
                if (! $requiresIncorp) {
                    $compatibleStageKeys = ['idea', 'pre_seed', 'seed', 'early_stage', 'growth', 'expansion', 'established'];
                } else {
                    // Corporate bond only growth/expansion/established
                    if ($code === 'corporate_bond') {
                        $compatibleStageKeys = ['growth', 'expansion', 'established'];
                    } elseif (in_array($code, ['bank_loan', 'term_loan', 'leasing', 'factoring', 'mezzanine_debt'])) {
                        $compatibleStageKeys = ['early_stage', 'growth', 'expansion', 'established'];
                    } else {
                        $compatibleStageKeys = ['pre_seed', 'seed', 'early_stage', 'growth', 'expansion', 'established'];
                    }
                }

                $stageIds = [];
                foreach ($compatibleStageKeys as $stageKey) {
                    if (isset($stages[$stageKey])) {
                        $stageIds[$stages[$stageKey]->id] = ['is_recommended' => true];
                    }
                }

                $inst->stages()->sync($stageIds);
            }
        }
    }
}
