<?php

namespace Database\Seeders;

use App\Enums\DrcProvince;
use App\Enums\FundingType;
use App\Models\Project;
use App\Models\Sector;
use App\Models\Stage;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * 10 demo projects, every one published and with 0 raised
 * (`funding_raised_minor` = 0). Standalone — NOT wired into
 * DatabaseSeeder, which deliberately keeps fabricated content out of the
 * default seed (spec §65/§69). Run explicitly:
 *
 *     php artisan db:seed --class=ProjectsSeeder
 *
 * Idempotent: a project is skipped if one with the same title already
 * exists (soft-deleted rows included).
 */
class ProjectsSeeder extends Seeder
{
    public function run(): void
    {
        if (! Role::where('name', 'entrepreneur')->exists()) {
            $this->call(RolesAndPermissionsSeeder::class);
        }

        if (Sector::count() === 0) {
            $this->call(SectorsSeeder::class);
        }

        if (Stage::count() === 0) {
            $this->call(StagesSeeder::class);
        }

        $owners = collect([
            ['name' => 'Béatrice Ilunga', 'email' => 'beatrice.ilunga@demo.investkongo.cd'],
            ['name' => 'Patrick Mbayo', 'email' => 'patrick.mbayo@demo.investkongo.cd'],
            ['name' => 'Grâce Nsimba', 'email' => 'grace.nsimba@demo.investkongo.cd'],
        ])->map(function (array $o) {
            return User::where('email', $o['email'])->first()
                ?? User::factory()->entrepreneur()->create($o);
        });

        foreach ($this->projects() as $i => $def) {
            if (Project::withTrashed()->where('title', $def['title'])->exists()) {
                $this->command?->warn("Skipped (exists): {$def['title']}");

                continue;
            }

            $sector = $def['sector'];
            $stage = $def['stage'];
            unset($def['sector'], $def['stage']);

            $project = Project::factory()
                ->for($owners[$i % $owners->count()])
                ->published()
                ->create($def + [
                    'funding_currency' => 'USD',
                    'funding_raised_minor' => 0, // "0 $ investi" — the whole point of this seed
                ]);

            $project->sectors()->sync(array_filter([
                Sector::where('name', $sector)->value('id') ?? Sector::inRandomOrder()->value('id'),
            ]));
            $project->stages()->sync(array_filter([
                Stage::where('name', $stage)->value('id') ?? Stage::inRandomOrder()->value('id'),
            ]));
        }

        $this->command?->info('Seeded '.Project::whereIn('title', collect($this->projects())->pluck('title'))->count().' demo projects (0 raised).');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function projects(): array
    {
        $member = fn (string $name, string $role) => [
            'full_name' => $name,
            'role' => $role,
            'linkedin' => '',
            'email' => '',
            'phone' => '',
        ];

        $usd = fn (int $dollars) => $dollars * 100;

        return [
            [
                'title' => 'AgriLink RDC',
                'sector' => 'Agriculture',
                'stage' => 'Amorçage',
                'description' => 'Plateforme logistique reliant les petits producteurs maraîchers du Plateau des Batéké aux marchés et restaurants de Kinshasa, avec collecte groupée et paiement mobile à la livraison.',
                'executive_summary' => 'AgriLink agrège l’offre de centaines de petits producteurs, organise la collecte froide et livre en 24 h aux acheteurs urbains. Le modèle réduit les pertes post-récolte et sécurise un revenu régulier pour les agriculteurs.',
                'problem' => 'Jusqu’à 40 % des produits maraîchers se perdent entre le champ et la ville faute de chaîne du froid et de transport fiable ; les producteurs vendent à perte aux intermédiaires.',
                'solution' => 'Points de collecte réfrigérés, application de commande pour les acheteurs, flotte de véhicules partagés et paiement mobile automatique au producteur dès la pesée.',
                'business_model' => 'Commission de 12 % sur chaque transaction, plus un abonnement mensuel pour les restaurants qui commandent en gros.',
                'market' => 'Kinshasa consomme plus de 1,5 million de tonnes de produits frais par an ; le segment B2B (hôtels, restaurants, cantines) représente environ 180 M$ adressables.',
                'competitive_advantage' => 'Réseau de collecte déjà implanté dans 6 villages, contrats-cadres avec 3 chaînes de restauration, et données de traçabilité que les intermédiaires informels ne peuvent pas fournir.',
                'traction' => '320 producteurs enregistrés, 45 acheteurs B2B actifs, 18 t livrées le dernier mois sans campagne marketing.',
                'financial_information' => 'Chiffre d’affaires mensuel de ~9 000 $, marge brute 22 %, seuil de rentabilité estimé à 40 t/mois.',
                'impact' => 'Revenu moyen des producteurs partenaires en hausse de 35 % ; 60 % des membres sont des femmes.',
                'risks' => 'Saisonnalité forte, dépendance au carburant, risque de contournement par les producteurs une fois mis en relation.',
                'funding_target_minor' => $usd(250000),
                'funding_min_minor' => $usd(100000),
                'funding_max_minor' => $usd(400000),
                'investment_types' => [FundingType::Equity->value, FundingType::ConvertibleNote->value, FundingType::Grant->value],
                'provinces' => [DrcProvince::Kinshasa->value],
                'cities' => ['Kinshasa'],
                'team' => [$member('Béatrice Ilunga', 'CEO'), $member('José Kabeya', 'Directeur des opérations')],
            ],
            [
                'title' => 'SolarKivu',
                'sector' => 'Énergie',
                'stage' => 'Démarrage',
                'description' => 'Kits solaires domestiques en location-vente (pay-as-you-go) pour les ménages hors réseau du Sud-Kivu, activés et suivis par mobile.',
                'executive_summary' => 'SolarKivu installe des kits solaires payés par petits versements mobiles hebdomadaires. Après 24 mois le client est propriétaire. Le suivi à distance permet de désactiver un kit en cas d’impayé et de le réactiver au paiement.',
                'problem' => 'Moins de 20 % des ménages ruraux du Sud-Kivu ont accès à l’électricité ; l’alternative est le pétrole lampant, cher et dangereux.',
                'solution' => 'Kit (panneau, batterie, 3 lampes, prise USB) financé sur 24 mois via mobile money, réseau d’agents locaux pour l’installation et le service après-vente.',
                'business_model' => 'Marge sur le matériel plus intérêts implicites du crédit ; revenus récurrents de remplacement de batteries.',
                'market' => 'Environ 900 000 ménages non raccordés dans la province, dont ~250 000 solvables au tarif proposé.',
                'competitive_advantage' => 'Réseau de 40 agents formés, partenariat avec deux coopératives d’épargne pour le recouvrement, et logiciel de gestion des impayés adapté au terrain.',
                'traction' => '1 250 kits installés, taux de recouvrement à 30 jours de 92 %, liste d’attente de 800 ménages.',
                'financial_information' => 'Revenu mensuel récurrent de ~14 000 $, coût d’acquisition client 22 $, valeur vie client estimée 190 $.',
                'impact' => 'Économie moyenne de 6 $/mois par ménage, 3 750 personnes éclairées, réduction de l’usage du pétrole lampant.',
                'risks' => 'Volatilité du taux de change sur les importations, insécurité dans certaines zones, défaut de paiement en cas de choc économique local.',
                'funding_target_minor' => $usd(500000),
                'funding_min_minor' => $usd(200000),
                'funding_max_minor' => $usd(750000),
                'investment_types' => [FundingType::Debt->value, FundingType::Equity->value, FundingType::RevenueShare->value],
                'provinces' => [DrcProvince::SudKivu->value],
                'cities' => ['Bukavu'],
                'team' => [$member('Emmanuel Bisimwa', 'Fondateur & CEO'), $member('Rachel Muhindo', 'Responsable réseau agents')],
            ],
            [
                'title' => 'CongoPay',
                'sector' => 'Services financiers',
                'stage' => 'Croissance',
                'description' => 'Agrégateur de paiements qui permet aux commerçants et PME congolaises d’encaisser tous les mobile money et cartes via une seule intégration.',
                'executive_summary' => 'CongoPay fournit une API et un terminal léger unifiant les principaux portefeuilles mobiles et cartes. Les marchands reçoivent un règlement quotidien et un tableau de bord de trésorerie.',
                'problem' => 'Un commerçant doit gérer 4 ou 5 comptes opérateurs séparés, des réconciliations manuelles et des délais de règlement opaques.',
                'solution' => 'Intégration unique (API, lien de paiement, QR, terminal), règlement T+1, réconciliation automatique et export comptable.',
                'business_model' => 'Commission de 1,5 % par transaction, abonnement pour les fonctionnalités avancées (facturation, sous-comptes).',
                'market' => 'Plus de 120 000 PME formelles en RDC ; volume mobile money national supérieur à 15 Md$ par an et en forte croissance.',
                'competitive_advantage' => 'Contrats d’interconnexion déjà signés avec trois opérateurs, licence d’agrégation en cours, équipe technique locale de 9 personnes.',
                'traction' => '2 400 marchands actifs, 1,1 M$ de volume traité le dernier mois, croissance de 18 % par mois sur 6 mois.',
                'financial_information' => 'Revenu mensuel ~16 500 $, marge de contribution 55 %, trésorerie couvrant 9 mois.',
                'impact' => 'Réduction des coûts d’encaissement des PME, formalisation des flux, historique de transactions ouvrant l’accès au crédit.',
                'risks' => 'Évolution réglementaire de la banque centrale, dépendance aux conditions négociées avec les opérateurs, risque de fraude.',
                'funding_target_minor' => $usd(1200000),
                'funding_min_minor' => $usd(600000),
                'funding_max_minor' => $usd(2000000),
                'investment_types' => [FundingType::Equity->value, FundingType::ConvertibleNote->value, FundingType::Other->value],
                'provinces' => [DrcProvince::Kinshasa->value],
                'cities' => ['Kinshasa'],
                'team' => [$member('Grâce Nsimba', 'CEO'), $member('Yannick Tshibangu', 'CTO'), $member('Aline Mputu', 'Responsable conformité')],
            ],
            [
                'title' => 'MediStock',
                'sector' => 'Santé',
                'stage' => 'Pré-amorçage',
                'description' => 'Logiciel de gestion de stock et de traçabilité des médicaments pour les cliniques et pharmacies du Haut-Katanga, avec alertes de rupture et détection des contrefaçons.',
                'executive_summary' => 'MediStock numérise l’inventaire pharmaceutique, relie les cliniques à des grossistes vérifiés et scanne les codes des boîtes pour écarter les lots suspects.',
                'problem' => 'Ruptures fréquentes de médicaments essentiels et circulation de faux médicaments faute d’inventaire fiable et de canal d’approvisionnement contrôlé.',
                'solution' => 'Application mobile d’inventaire, catalogue de grossistes agréés, commandes groupées et vérification des codes-barres contre une base de lots.',
                'business_model' => 'Abonnement mensuel par établissement plus commission sur les commandes passées via la place de marché.',
                'market' => '~1 800 établissements de santé privés dans la province ; marché pharmaceutique national estimé à 700 M$.',
                'competitive_advantage' => 'Partenariat pilote avec un réseau de 12 cliniques, accord avec deux grossistes agréés, équipe mixte santé/tech.',
                'traction' => 'Pilote sur 12 cliniques, réduction des ruptures de 30 % en 3 mois, 4 grossistes intéressés.',
                'financial_information' => 'Pré-revenu ; coûts mensuels de 3 200 $ ; objectif de 150 établissements payants en 18 mois.',
                'impact' => 'Meilleure disponibilité des traitements essentiels, réduction de l’exposition aux médicaments falsifiés.',
                'risks' => 'Adoption lente par des établissements peu numérisés, fiabilité de la base de lots, dépendance à la qualité des grossistes partenaires.',
                'funding_target_minor' => $usd(150000),
                'funding_min_minor' => $usd(60000),
                'funding_max_minor' => $usd(250000),
                'investment_types' => [FundingType::Grant->value, FundingType::ConvertibleNote->value, FundingType::Equity->value],
                'provinces' => [DrcProvince::HautKatanga->value],
                'cities' => ['Lubumbashi'],
                'team' => [$member('Dr Christelle Kyungu', 'Fondatrice'), $member('Serge Ngoy', 'Développeur principal')],
            ],
            [
                'title' => 'CargoRoute',
                'sector' => 'Logistique',
                'stage' => 'Amorçage',
                'description' => 'Place de marché du fret routier reliant transporteurs et chargeurs sur le corridor Matadi–Kinshasa, avec suivi GPS et paiement sécurisé à la livraison.',
                'executive_summary' => 'CargoRoute publie les chargements disponibles, met en relation avec des transporteurs vérifiés, suit le camion en temps réel et libère le paiement à la preuve de livraison.',
                'problem' => 'Les camions roulent souvent à vide au retour, les chargeurs manquent de visibilité et les litiges de paiement sont fréquents.',
                'solution' => 'Bourse de fret en ligne, vérification des transporteurs, boîtier GPS, séquestre de paiement libéré à la livraison confirmée.',
                'business_model' => 'Commission de 8 % par chargement, location des boîtiers GPS, options d’assurance cargo.',
                'market' => 'Le corridor Matadi–Kinshasa concentre l’essentiel des importations du pays ; dépenses de transport routier estimées à 400 M$/an.',
                'competitive_advantage' => 'Base de 180 transporteurs vérifiés, intégration avec deux transitaires du port de Matadi, historique de fiabilité par transporteur.',
                'traction' => '260 chargements intermédiés, 180 transporteurs, taux de retour à vide des partenaires réduit de 25 %.',
                'financial_information' => 'Revenu mensuel ~7 800 $, marge brute 40 %, seuil de rentabilité à ~90 chargements/mois.',
                'impact' => 'Baisse des coûts logistiques, meilleurs revenus pour les transporteurs indépendants, moins de trajets à vide.',
                'risks' => 'État des routes, sécurité sur le corridor, désintermédiation après la mise en relation.',
                'funding_target_minor' => $usd(300000),
                'funding_min_minor' => $usd(120000),
                'funding_max_minor' => $usd(500000),
                'investment_types' => [FundingType::Equity->value, FundingType::Debt->value, FundingType::RevenueShare->value],
                'provinces' => [DrcProvince::KongoCentral->value],
                'cities' => ['Matadi'],
                'team' => [$member('Patrick Mbayo', 'CEO'), $member('Nadège Lelo', 'Responsable partenariats port')],
            ],
            [
                'title' => 'EduPlus',
                'sector' => 'Éducation',
                'stage' => 'Démarrage',
                'description' => 'Bibliothèque de contenus scolaires numériques fonctionnant hors-ligne pour les écoles secondaires, distribuée sur tablettes et boîtiers Wi-Fi locaux.',
                'executive_summary' => 'EduPlus fournit cours, exercices et évaluations alignés sur le programme national, préchargés sur un boîtier local que les élèves consultent sans connexion Internet.',
                'problem' => 'Manuels rares et coûteux, enseignants surchargés, connexion Internet quasi inexistante dans les écoles.',
                'solution' => 'Boîtier serveur local + tablettes, contenus conformes au programme, tableau de bord de progression pour l’enseignant, mises à jour trimestrielles.',
                'business_model' => 'Abonnement annuel par école selon le nombre d’élèves, vente ou location du matériel.',
                'market' => 'Plus de 30 000 écoles secondaires en RDC ; le segment privé urbain solvable représente ~4 000 établissements.',
                'competitive_advantage' => 'Contenu déjà validé par des inspecteurs pédagogiques, déploiement clé en main, coût par élève inférieur au manuel papier.',
                'traction' => '22 écoles équipées, 6 800 élèves actifs, taux de renouvellement des écoles pilotes de 100 %.',
                'financial_information' => 'Revenu annuel récurrent ~48 000 $, marge brute 60 %, cycle de vente de 2 mois.',
                'impact' => 'Accès élargi à des ressources pédagogiques de qualité, meilleurs résultats aux évaluations dans les classes pilotes.',
                'risks' => 'Budget limité des écoles, maintenance du matériel, évolution du programme officiel.',
                'funding_target_minor' => $usd(400000),
                'funding_min_minor' => $usd(150000),
                'funding_max_minor' => $usd(600000),
                'investment_types' => [FundingType::Equity->value, FundingType::Grant->value, FundingType::ConvertibleNote->value],
                'provinces' => [DrcProvince::Kinshasa->value],
                'cities' => ['Kinshasa'],
                'team' => [$member('Miradie Kalala', 'CEO'), $member('Fiston Lokwa', 'Responsable pédagogique')],
            ],
            [
                'title' => 'PisciCongo',
                'sector' => 'Agriculture',
                'stage' => 'Pré-amorçage',
                'description' => 'Fermes piscicoles en cages flottantes sur la rivière Kwilu, produisant du tilapia pour les marchés de Bandundu et de Kikwit.',
                'executive_summary' => 'PisciCongo installe des cages flottantes, forme des jeunes pisciculteurs et vend le tilapia frais et fumé via un réseau de revendeuses.',
                'problem' => 'Le poisson consommé dans la région est majoritairement importé et congelé ; la production locale est artisanale et irrégulière.',
                'solution' => 'Cages standardisées, alevins et aliments de qualité fournis par la ferme, appui technique et rachat garanti de la production.',
                'business_model' => 'Vente directe du poisson, plus vente d’intrants (alevins, aliments) aux pisciculteurs partenaires.',
                'market' => 'Consommation régionale estimée à 12 000 t/an, dont plus de 70 % importées.',
                'competitive_advantage' => 'Site fluvial sécurisé, accord avec une écloserie régionale, réseau de 25 revendeuses.',
                'traction' => '8 cages en exploitation, 6 t produites sur le premier cycle, 25 revendeuses sous contrat.',
                'financial_information' => 'Revenu par cycle (~5 mois) de 9 500 $ par lot de 8 cages, marge brute 35 %.',
                'impact' => 'Protéines abordables pour les ménages, emplois pour les jeunes ruraux, substitution aux importations.',
                'risks' => 'Maladies des poissons, crues et étiages du Kwilu, prix des aliments importés.',
                'funding_target_minor' => $usd(120000),
                'funding_min_minor' => $usd(50000),
                'funding_max_minor' => $usd(200000),
                'investment_types' => [FundingType::Grant->value, FundingType::Debt->value, FundingType::Equity->value],
                'provinces' => [DrcProvince::Kwilu->value],
                'cities' => ['Bandundu'],
                'team' => [$member('Espérance Mawete', 'Fondatrice'), $member('Blaise Kimbeni', 'Technicien piscicole')],
            ],
            [
                'title' => 'BriqueVerte',
                'sector' => 'Industrie manufacturière',
                'stage' => 'Amorçage',
                'description' => 'Fabrication de briques de construction écologiques compressées à partir de déchets plastiques collectés dans Kinshasa.',
                'executive_summary' => 'BriqueVerte transforme le plastique post-consommation en briques et pavés plus légers et moins chers que le parpaing de ciment, avec une filière de collecte rémunérée.',
                'problem' => 'Kinshasa produit des milliers de tonnes de déchets plastiques non recyclés, et le coût du ciment rend la construction inaccessible.',
                'solution' => 'Unité de broyage et de compression, réseau de collecteurs payés au kilo, gamme de briques et pavés certifiés en résistance.',
                'business_model' => 'Vente de briques et pavés aux particuliers et PME du BTP, plus contrats de collecte avec des entreprises.',
                'market' => 'Marché des matériaux de construction de Kinshasa estimé à plus de 300 M$/an, forte demande de logement abordable.',
                'competitive_advantage' => 'Formule brevetable, prix 20 % sous le parpaing, image environnementale valorisée par les ONG partenaires.',
                'traction' => 'Unité pilote produisant 1 500 briques/jour, 3 chantiers clients, 40 collecteurs actifs.',
                'financial_information' => 'Revenu mensuel ~11 000 $, marge brute 45 %, capacité limitée par une seule ligne de production.',
                'impact' => 'Détournement de déchets plastiques de la nature, revenus pour les collecteurs, logement moins cher.',
                'risks' => 'Approvisionnement irrégulier en plastique, acceptation du produit par les maçons, coût de l’énergie.',
                'funding_target_minor' => $usd(280000),
                'funding_min_minor' => $usd(100000),
                'funding_max_minor' => $usd(450000),
                'investment_types' => [FundingType::Equity->value, FundingType::Debt->value, FundingType::Grant->value],
                'provinces' => [DrcProvince::Kinshasa->value],
                'cities' => ['Kinshasa'],
                'team' => [$member('Dieudonné Masamba', 'CEO'), $member('Carine Bope', 'Responsable production')],
            ],
            [
                'title' => 'TeraMines Data',
                'sector' => 'Mines',
                'stage' => 'Croissance',
                'description' => 'Plateforme de conformité et de traçabilité pour les coopératives minières artisanales du Lualaba, documentant l’origine du cuivre et du cobalt du site à l’acheteur.',
                'executive_summary' => 'TeraMines Data enregistre chaque lot, sa localisation, ses conditions d’extraction et sa chaîne de garde, et produit les rapports exigés par les acheteurs internationaux.',
                'problem' => 'Les acheteurs refusent les minerais artisanaux non traçables ; les coopératives n’ont pas les outils pour prouver une extraction responsable.',
                'solution' => 'Application terrain hors-ligne, étiquetage des sacs, tableau de bord de conformité et export des rapports au format des standards du secteur.',
                'business_model' => 'Abonnement par coopérative plus frais par lot certifié ; contrats avec les négociants exportateurs.',
                'market' => 'Le Lualaba compte des centaines de coopératives ; la traçabilité est désormais une condition d’accès aux chaînes d’approvisionnement des constructeurs.',
                'competitive_advantage' => 'Accord avec deux négociants exportateurs, méthode validée par un auditeur tiers, ancrage local à Kolwezi.',
                'traction' => '14 coopératives sous abonnement, 3 200 lots tracés, 2 contrats-cadres avec des exportateurs.',
                'financial_information' => 'Revenu mensuel ~19 000 $, marge de contribution 65 %, pipeline de 20 coopératives.',
                'impact' => 'Meilleur accès au marché formel pour les mineurs artisanaux, réduction du travail des enfants sur les sites suivis.',
                'risks' => 'Contexte réglementaire mouvant, fiabilité des déclarations terrain, dépendance à quelques acheteurs.',
                'funding_target_minor' => $usd(900000),
                'funding_min_minor' => $usd(400000),
                'funding_max_minor' => $usd(1500000),
                'investment_types' => [FundingType::Equity->value, FundingType::ConvertibleNote->value, FundingType::Other->value],
                'provinces' => [DrcProvince::Lualaba->value],
                'cities' => ['Kolwezi'],
                'team' => [$member('Hervé Kapend', 'CEO'), $member('Sandrine Ilunga', 'Responsable conformité'), $member('Olivier Mwamba', 'Lead produit')],
            ],
            [
                'title' => 'AquaClaire',
                'sector' => 'Infrastructure',
                'stage' => 'Démarrage',
                'description' => 'Réseau de kiosques de purification et de distribution d’eau potable dans les quartiers périphériques de Kisangani, avec abonnement par bidon.',
                'executive_summary' => 'AquaClaire installe des unités de traitement compactes et des kiosques de proximité ; les ménages achètent des jetons pour remplir leurs bidons à un prix inférieur à celui de l’eau vendue en sachets.',
                'problem' => 'Une grande partie de la population n’a pas d’eau potable au robinet et s’approvisionne à des sources contaminées ou à l’eau en sachet, chère et peu fiable.',
                'solution' => 'Unités de filtration et chloration, kiosques tenus par des gérants locaux, paiement par jetons prépayés et suivi de la qualité à distance.',
                'business_model' => 'Vente d’eau au volume via jetons, plus livraison à domicile sur abonnement pour les commerces.',
                'market' => 'Kisangani compte plus d’un million d’habitants, dont la majorité dans des zones non desservies par le réseau.',
                'competitive_advantage' => 'Autorisation municipale pour 10 sites, coût au litre inférieur de 40 % au sachet, contrôle qualité documenté.',
                'traction' => '4 kiosques ouverts, 2 100 ménages abonnés, volume mensuel de 380 000 litres.',
                'financial_information' => 'Revenu mensuel ~8 900 $, marge brute 50 %, chaque kiosque rentable en ~7 mois.',
                'impact' => 'Baisse des maladies hydriques dans les zones desservies, emplois de gérants, prix de l’eau maîtrisé.',
                'risks' => 'Coût de l’énergie pour le pompage et le traitement, entretien des équipements, concurrence de l’eau en sachet informelle.',
                'funding_target_minor' => $usd(350000),
                'funding_min_minor' => $usd(150000),
                'funding_max_minor' => $usd(550000),
                'investment_types' => [FundingType::Debt->value, FundingType::Equity->value, FundingType::Grant->value],
                'provinces' => [DrcProvince::Tshopo->value],
                'cities' => ['Kisangani'],
                'team' => [$member('Josué Lofembe', 'CEO'), $member('Prisca Amisi', 'Responsable exploitation')],
            ],
        ];
    }
}
