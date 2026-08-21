<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            // General
            ['General', 'Qu\'est-ce que Kanda ?', 'Kanda est une plateforme qui met en relation des entrepreneurs basés en République Démocratique du Congo avec des investisseurs du monde entier, à l\'aide d\'un moteur de matching combinant règles métier et analyse par intelligence artificielle.'],
            ['General', 'Kanda est-il disponible en français ?', 'Oui, le français est la langue principale de la plateforme. Une version anglaise est prévue.'],
            ['General', 'Qui peut créer un compte ?', 'Deux profils sont possibles à l\'inscription : entrepreneur (projet basé en RDC) ou investisseur (basé n\'importe où). \"Professionnel\" est un plan d\'abonnement, accessible aux deux profils, pas un type de compte séparé.'],

            // Entrepreneurs
            ['Entrepreneurs', 'Mon projet doit-il être basé en RDC ?', 'Oui. C\'est une règle appliquée au niveau de la base de données, pas seulement dans le formulaire : un projet entrepreneur ne peut pas être enregistré avec un pays autre que la RDC.'],
            ['Entrepreneurs', 'Combien de projets actifs puis-je avoir ?', 'Le plan Free autorise un seul projet actif à la fois. Le plan Entrepreneur permet d\'en avoir plusieurs. Vous pouvez archiver un projet existant pour en créer un nouveau.'],
            ['Entrepreneurs', 'Comment publier mon projet ?', 'Depuis « Mes projets », après avoir renseigné au moins un secteur, un stade et un objectif de financement, le bouton « Publier » devient disponible. La vérification est faite côté serveur, pas seulement dans l\'interface.'],

            // Investors
            ['Investors', 'Je ne suis pas basé en RDC, puis-je investir ?', 'Oui. Contrairement aux entrepreneurs, les investisseurs peuvent être basés dans n\'importe quel pays.'],
            ['Investors', 'Puis-je sélectionner plusieurs secteurs et stades ?', 'Oui, votre profil investisseur autorise une sélection multiple de secteurs et de stades — le moteur de matching en tient compte pour chaque projet.'],
            ['Investors', 'Comment sont calculées mes recommandations de projets ?', 'Chaque projet publié reçoit un score de compatibilité avec votre profil, recalculé à chaque mise à jour de vos préférences. Le détail du calcul (par critère) est toujours visible, jamais une simple note brute.'],

            // Matching
            ['Matching', 'Comment fonctionne le score de compatibilité ?', 'Le score combine des règles objectives (secteur, stade, financement, géographie, risque) et une analyse sémantique du modèle économique, de la traction et de l\'impact. Le poids de chaque critère est configurable par l\'équipe Kanda.'],
            ['Matching', 'Le score utilise-t-il uniquement l\'IA ?', 'Non. Cinq des huit critères sont calculés par des règles déterministes, sans IA. Seuls trois critères plus qualitatifs (modèle économique, traction, impact) peuvent utiliser une analyse IA lorsqu\'elle est activée ; sinon un score de complétude neutre est utilisé.'],
            ['Matching', 'Que se passe-t-il si l\'analyse IA échoue ?', 'Le calcul du score continue normalement grâce à une estimation de secours — une panne de l\'IA n\'empêche jamais d\'obtenir un score.'],

            // Subscriptions
            ['Subscriptions', 'Quels sont les plans disponibles ?', 'Free (gratuit, 1 projet actif), Entrepreneur, Investor, et Professional. Chaque plan a des limites et fonctionnalités propres, visibles sur la page Tarifs.'],
            ['Subscriptions', 'Puis-je changer de plan à tout moment ?', 'Oui, depuis les paramètres de votre compte. Le changement de plan est immédiat.'],
            ['Subscriptions', 'Un entrepreneur peut-il choisir le plan Investor ?', 'Non. Chaque plan payant est réservé au rôle correspondant ; seul le plan Free est disponible pour tous les rôles.'],

            // Payments
            ['Payments', 'Quels moyens de paiement seront supportés ?', 'Cartes bancaires (Stripe), PayPal, et Mobile Money (Orange Money, M-Pesa, Airtel Money) pour les flux locaux en RDC. L\'intégration des paiements réels est en cours de développement.'],
            ['Payments', 'Les montants sont-ils en dollars ?', 'Les montants peuvent être exprimés en USD, EUR ou CDF selon le contexte. Chaque transaction précise explicitement sa devise.'],

            // Escrow
            ['Escrow', 'Qu\'est-ce que le séquestre (Escrow) ?', 'Un mécanisme où les fonds d\'un investisseur sont retenus jusqu\'à confirmation des deux parties, avant d\'être libérés à l\'entrepreneur ou remboursés à l\'investisseur.'],
            ['Escrow', 'Le séquestre est-il déjà actif ?', 'Le modèle de données et les règles métier sont conçus, mais le système d\'Escrow n\'est pas encore activé sur la plateforme.'],

            // KYC
            ['Kyc', 'Pourquoi dois-je fournir des documents d\'identité ?', 'Le KYC (Know Your Customer) est une étape de vérification requise avant certaines actions financières, pour la sécurité de toutes les parties.'],
            ['Kyc', 'Mes documents KYC sont-ils publics ?', 'Non, jamais. Ils sont stockés sur un espace privé, jamais accessibles par une URL directe, et consultables uniquement par les personnes autorisées.'],

            // Contracts
            ['Contracts', 'Kanda génère-t-il des contrats juridiquement valables ?', 'Kanda peut générer un contrat structuré à partir des conditions d\'un deal, mais ne remplace pas un conseil juridique. Une validation humaine reste nécessaire.'],

            // Security
            ['Security', 'Mes coordonnées sont-elles visibles publiquement ?', 'Non. Vous contrôlez individuellement la visibilité de votre email, téléphone, site web et réseaux sociaux dans vos paramètres.'],
            ['Security', 'Kanda utilise-t-il l\'authentification à deux facteurs ?', 'Oui, la 2FA est disponible et peut être activée depuis les paramètres de sécurité de votre compte.'],
        ];

        foreach ($faqs as $i => [$category, $question, $answer]) {
            Faq::updateOrCreate(
                ['question' => $question],
                ['category' => $category, 'answer' => $answer, 'order' => $i, 'is_published' => true],
            );
        }
    }
}
