# Limites de Financement des Projets

## Vue d'ensemble

Cette fonctionnalité impose des limites minimales et maximales sur les montants de financement des projets (`funding_target_minor`) pour maintenir la cohérence et la crédibilité de la plateforme KongoInvest.

## Limites par Devise

| Devise | Minimum | Maximum |
|--------|---------|---------|
| USD    | 2 000 $ | 100 000 $ |
| CDF    | 5 000 000 CDF | 250 000 000 CDF |
| EUR    | 2 000 € | 100 000 € |

> **Note:** Toutes les valeurs sont stockées en centimes (unités mineures) dans la base de données.

## Fonctionnalités

### 1. Validation en temps réel

Les formulaires de création et de modification de projets valident automatiquement que le montant cible respecte les limites définies pour la devise choisie.

**Fichiers concernés:**
- `app/Http/Requests/StoreProjectRequest.php`
- `app/Http/Requests/UpdateProjectRequest.php`
- `app/Rules/WithinFundingLimits.php`

### 2. Système de notification

Les entrepreneurs dont les projets existants dépassent les limites reçoivent des notifications in-app les invitant à corriger leurs montants.

**Fichiers concernés:**
- `app/Notifications/ProjectFundingLimitNotification.php`
- `app/Console/Commands/CheckProjectFundingLimits.php`

### 3. Grace Period de 14 jours

Les entrepreneurs disposent de 14 jours pour corriger les montants hors limites. Après ce délai, des rappels continuent d'apparaître mais aucun blocage automatique n'est appliqué.

### 4. Commande Artisan

Une commande artisan vérifie quotidiennement les projets et gère les notifications :

```bash
# Exécution normale
php artisan projects:check-funding-limits

# Mode simulation (dry-run)
php artisan projects:check-funding-limits --dry-run
```

La commande est programmée pour s'exécuter automatiquement tous les jours à 8h du matin via le scheduler Laravel.

### 5. Tracking dans la base de données

Deux nouvelles colonnes ont été ajoutées à la table `projects` :

- `funding_limit_notified_at` : Date d'envoi de la notification initiale
- `funding_limit_deadline` : Date limite pour corriger le montant (notification + 14 jours)

## Architecture

### Services

**`FundingLimitService`** (`app/Services/FundingLimitService.php`)

Service centralisé qui encapsule toute la logique métier des limites de financement :

```php
// Obtenir les limites pour une devise
$limits = $service->getLimits('USD'); // ['min' => 200_000, 'max' => 10_000_000]

// Vérifier si un montant est valide
$isValid = $service->isWithinLimits(5_000_000, 'USD'); // true

// Obtenir un message de validation
$message = $service->getValidationMessage('USD'); // "Le montant cible doit être entre..."

// Formater un montant
$formatted = $service->formatAmount(5_000_000, 'USD'); // "50 000 USD"
```

### Méthodes du Modèle Project

Le modèle `Project` dispose de nouvelles méthodes helper :

```php
// Vérifie si le projet est hors limites
$project->isOutsideFundingLimits(); // bool

// Vérifie si la deadline est dépassée
$project->hasFundingLimitDeadlinePassed(); // bool

// Détermine si un warning doit être affiché
$project->shouldShowFundingLimitWarning(); // bool
```

## Tests

La fonctionnalité est entièrement testée :

**Tests unitaires** (`tests/Unit/FundingLimitServiceTest.php`)
- 16 tests couvrant toutes les méthodes du `FundingLimitService`

**Tests de feature** (`tests/Feature/ProjectFundingLimitsTest.php`)
- 14 tests couvrant la validation, les notifications, la commande artisan et les méthodes du modèle

```bash
# Exécuter tous les tests de limites de financement
php artisan test --filter=FundingLimit

# Exécuter uniquement les tests unitaires
php artisan test --filter=FundingLimitServiceTest

# Exécuter uniquement les tests de feature
php artisan test --filter=ProjectFundingLimitsTest
```

## Migration

Pour appliquer les nouvelles colonnes à la base de données :

```bash
php artisan migrate
```

Pour revenir en arrière :

```bash
php artisan migrate:rollback
```

## FAQ

Une nouvelle entrée a été ajoutée à la FAQ dans la section "Entrepreneurs" :

**Question :** "Quelles sont les limites de montant pour mon projet ?"

**Réponse :** Explique les limites par devise, leur raison d'être et le processus de notification.

Pour mettre à jour la FAQ en base de données :

```bash
php artisan db:seed --class=FaqSeeder
```

## Interface Utilisateur (Terminé ✅)

### Task 10: Texte d'aide dans les formulaires React ✅

Un helper text a été ajouté sous le champ `funding_target` dans le formulaire de projet (`resources/js/pages/Entrepreneur/Projects/Form.tsx`).

**Fonctionnalités implémentées :**
- Affiche les limites min/max pour la devise sélectionnée
- Se met à jour dynamiquement quand la devise change
- Visuellement cohérent avec le reste du formulaire (texte gris caption)
- N'apparaît que si aucune erreur de validation n'est présente

**Exemples de textes affichés :**
- USD : "Montant entre 2 000 et 100 000 USD"
- CDF : "Montant entre 5 000 000 et 250 000 000 CDF"
- EUR : "Montant entre 2 000 et 100 000 EUR"

### Task 11: Composant d'avertissement React ✅

Le composant `FundingLimitWarning.tsx` a été créé et intégré dans la page liste des projets de l'entrepreneur.

**Fonctionnalités implémentées :**
- Composant React dans `resources/js/components/FundingLimitWarning.tsx`
- Affiche une bannière ambre (ou rouge si deadline dépassée)
- Montre le nombre de jours restants avant la deadline
- Inclut un bouton "Modifier mon projet" vers l'édition
- Adapte le ton et la couleur si la deadline est dépassée
- Accessible (attributs ARIA appropriés)

**Intégration côté serveur :**
- Controller `ProjectController::index()` modifié pour passer les données via Inertia
- Utilise `shouldShowFundingLimitWarning()` pour déterminer l'affichage
- Inclut toutes les informations nécessaires (deadline, montants, jours restants)
- Type TypeScript `ProjectRow` mis à jour avec `fundingLimitWarning`

**Affichage :**
- S'affiche en haut de chaque projet concerné sur `/entrepreneur/projects`
- Design cohérent avec le design system existant (Tailwind)
- Responsive et fonctionne sur mobile

## Maintenance

### Ajouter une nouvelle devise

Pour ajouter une nouvelle devise supportée :

1. Modifier `app/Services/FundingLimitService.php` :
```php
private const LIMITS = [
    // ... devises existantes
    'GBP' => [
        'min' => 200_000,      // 2 000 £
        'max' => 10_000_000,   // 100 000 £
    ],
];
```

2. Ajouter des tests dans `tests/Unit/FundingLimitServiceTest.php`

3. Mettre à jour la documentation (ce fichier + FAQ)

### Modifier les limites existantes

Modifier simplement les valeurs dans la constante `LIMITS` du `FundingLimitService`. Les changements prendront effet immédiatement sans migration nécessaire.

### Modifier le grace period

Modifier la constante `GRACE_PERIOD_DAYS` dans `app/Console/Commands/CheckProjectFundingLimits.php`.

## Considérations futures

- **Email de notification** : Actuellement seules les notifications in-app sont envoyées. Un canal email pourrait être ajouté.
- **Dashboard administrateur** : Un panneau permettant aux administrateurs de voir tous les projets hors limites.
- **Statistiques** : Tracking du nombre de projets corrigés vs non corrigés après notification.
- **Dépublication automatique** : Actuellement les rappels sont infinis. Une option de dépublication automatique pourrait être ajoutée.

## Support

Pour toute question ou problème lié à cette fonctionnalité, contacter l'équipe de développement.

---

**Dernière mise à jour :** 29 octobre 2026  
**Version :** 1.0.0  
**Statut :** ✅ Implémentation complète (Backend + Frontend)
