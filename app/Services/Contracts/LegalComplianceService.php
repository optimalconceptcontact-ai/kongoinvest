<?php

namespace App\Services\Contracts;

use App\Enums\CheckSeverity;
use App\Enums\CheckStatus;
use App\Enums\ContractDocumentStatus;
use App\Enums\ContractDocumentType;
use App\Enums\EquityMechanism;
use App\Enums\FinancingComponentType;
use App\Enums\LegalCheckType;
use App\Enums\LegalForm;
use App\Models\CompanyLegalRecord;
use App\Models\ContractFinancingComponent;
use App\Models\InvestmentContractWorkspace;
use App\Models\LegalComplianceCheck;
use App\Services\Contracts\Data\LegalCheckDraft;
use App\Services\Contracts\Data\LegalComplianceRunResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Contrôles juridiques automatiques d'une opération (prompt §10
 * — Contrôles juridiques). Comme les contrôles financiers :
 * déterministes, exécutables à la demande, écrits en batch —
 * une relance crée un lot nouveau, l'historique de contrôle
 * reste intact. Aucun contrôle n'est délégué à l'IA.
 *
 * Sources fiables uniquement : fiche juridique courante
 * (CompanyLegalRecord), structure de détention enregistrée
 * (CompanyShareholding), documents contractuels, composantes de
 * financement, pays de l'entreprise. Quand une donnée manque, le
 * contrôle émet NeedsReview — jamais de réponse inventée
 * (prompt §16, règle 12). Les actes (AUSCGIE, droit des
 * sûretés OHADA, réglementation financière congolaise) sont
 * convoqués en texte de message : la grille LegalReference,
 * non seedée, n'est jamais invoquée comme source.
 *
 * Niveaux :
 * - Critique + échec : contradiction de matière juridique
 *   (aucune décision sociale, acte de souscription / cession
 *   de titres absent) — fige la finalisation.
 * - Élevé : divergence correction requise (forme
 *   unipersonnelle≠pluripersonnelle, capital≠associés,
 *   SARL au-delà de 50 associés, base de conversion absente).
 * - Moyen : données manquantes à documenter.
 * - Faible : conforme ou hors objet documenté.
 */
class LegalComplianceService
{
    /**
     * Exécute l'ensemble des contrôles juridiques et écrit un
     * batch. Même mécanique harness que le service financier :
     * exécutable dans la même passe.
     */
    public function run(InvestmentContractWorkspace $workspace): LegalComplianceRunResult
    {
        $batch = 'leg-'.Str::uuid()->toString();
        /** @var list<LegalCheckDraft> $checks */
        $checks = [];

        $workspace->loadMissing(['company.legalRecords', 'financingComponents', 'documents']);

        $components = $workspace->financingComponents;
        $entryComponents = $components->filter(
            fn (ContractFinancingComponent $component) => $component->confersCapitalEntry(),
        );

        $mechanisms = $components
            ->map(fn (ContractFinancingComponent $component) => $component->equity_mechanism)
            ->filter(fn ($mechanism) => $mechanism !== null)
            ->unique();
        $capitalEntry = $entryComponents->isNotEmpty()
            || $mechanisms->contains(EquityMechanism::CapitalIncrease);
        $transferNeeded = $mechanisms->contains(EquityMechanism::TitleTransfer);
        $subscriptionNeeded = $capitalEntry
            || $mechanisms->contains(EquityMechanism::CapitalIncrease);
        $decisionNeeded = $capitalEntry || $transferNeeded;
        $financing = $components->isNotEmpty();

        /** @var CompanyLegalRecord|null $record */
        $record = $workspace->company?->legalRecords->first(
            fn ($legalRecord) => $legalRecord->is_current,
        );

        $documentTypes = $workspace->documents
            ->reject(fn ($document) => $document->status === ContractDocumentStatus::Cancelled)
            ->pluck('type')
            ->unique();

        // --- 1. Forme sociale vs structure de détention -------------
        $this->checkLegalFormCompatibility($batch, $checks, $workspace, $record);
        // --- 2. Conformité AUSCGIE ----------------------------------
        $this->checkAuscgieCompliance($batch, $checks, $capitalEntry, $record);
        // --- 3. Droit des sûretés -----------------------------------
        $this->checkSecurityLawCompliance($batch, $checks, $components, $documentTypes);
        // --- 4. Cohérence avec les statuts --------------------------
        if ($capitalEntry) {
            $this->checkStatutesCoherence($batch, $checks, $record);
        }
        // --- 5. Compétence des organes sociaux ----------------------
        if ($decisionNeeded) {
            $this->checkOrganCompetence($batch, $checks, $record);
        }
        // --- 6. Validité du mécanisme de titres ---------------------
        $this->checkTitleMechanismValidity($batch, $checks, $capitalEntry, $record);
        // --- 7. Décisions sociales requises -------------------------
        if ($decisionNeeded || $financing) {
            $this->checkCorporateDecisions($batch, $checks, $decisionNeeded, $documentTypes);
        }
        // --- 8. Formalités applicables ------------------------------
        if ($financing) {
            $this->checkFormalities($batch, $checks, $subscriptionNeeded, $transferNeeded, $documentTypes, $record);
        }
        // --- 9. Réglementation financière RDC -----------------------
        if ($financing) {
            $this->checkRdcFinancialRegulation($batch, $checks, $workspace);
        }
        // --- 10. Restrictions investisseurs étrangers ---------------
        if ($financing && $record !== null) {
            $this->checkBeneficialOwners($batch, $checks, $record);
        }
        // --- 11. Convertibilité de l'instrument ---------------------
        $this->checkConvertibility($batch, $checks, $components, $record);

        foreach ($checks as $check) {
            LegalComplianceCheck::forceCreate([
                'investment_contract_workspace_id' => $workspace->id,
                'batch' => $batch,
                'type' => $check->type->value,
                'severity' => $check->severity->value,
                'status' => $check->status->value,
                'title' => $check->title,
                'message' => $check->message,
                'details' => $check->details,
                'references' => $check->references,
            ]);
        }

        $counts = collect($checks)->countBy(fn (LegalCheckDraft $check) => $check->status->value)->all();
        $blocking = collect($checks)->contains(
            fn (LegalCheckDraft $check) => $check->status === CheckStatus::Failed && $check->severity->blocksClosing(),
        );

        return new LegalComplianceRunResult($batch, $counts, $blocking, array_map(
            fn (LegalCheckDraft $check) => $check->toArray(),
            $checks,
        ));
    }

    /**
     * Des critères critiques non résolus bloquent la clôture
     * (prompt §10) — même filtre que le service financier. La
     * porte évalue la DERNIÈRE passe : une relance refait le
     * présent — l'historique antérieur reste consultable mais
     * jamais bloquant (même contrat que le payload React).
     */
    public function hasOpenBlockingChecks(InvestmentContractWorkspace $workspace): bool
    {
        $latestBatch = LegalComplianceCheck::query()
            ->where('investment_contract_workspace_id', $workspace->id)
            ->orderByDesc('id')
            ->value('batch');

        if ($latestBatch === null) {
            return false;
        }

        return LegalComplianceCheck::query()
            ->where('investment_contract_workspace_id', $workspace->id)
            ->where('batch', $latestBatch)
            ->where('severity', CheckSeverity::Critical->value)
            ->where('status', CheckStatus::Failed->value)
            ->whereNull('resolved_at')
            ->exists();
    }

    /**
     * @param  list<LegalCheckDraft>  $checks
     */
    private function checkLegalFormCompatibility(
        string $batch,
        array &$checks,
        InvestmentContractWorkspace $workspace,
        ?CompanyLegalRecord $record,
    ): void {
        if ($record === null) {
            $this->addCheck($batch, $checks, LegalCheckType::LegalFormCompatibility, CheckStatus::NeedsReview, CheckSeverity::Medium,
                'Fiche juridique de la société manquante',
                'Aucune fiche juridique courante n\'est rattachée à l\'entreprise concernée : la forme sociale ne peut pas être confrontée au mécanisme de l\'opération. Enregistrer la fiche (module « Fiche juridique ») — rien n\'est déduit à sa place.',
                ['company_id' => $workspace->company_id],
            );

            return;
        }

        $form = $record->form();
        $single = $record->isSingleMember();

        if ($single !== null && $form->isSingleMemberForm() !== $single) {
            $this->addCheck($batch, $checks, LegalCheckType::LegalFormCompatibility, CheckStatus::Warning, CheckSeverity::High,
                'Divergence forme sociale / structure des associés',
                sprintf(
                    'La forme %1$s est enregistrée comme société %2$s mais la structure de détention compte %3$d associé(s). La divergence est signalée, jamais corrigée : vérifier la fiche juridique ou la décomposition réelle du capital.',
                    $form->label(),
                    $form->isSingleMemberForm() ? 'unipersonnelle' : 'pluripersonnelle',
                    $record->shareholdings->count(),
                ),
                ['legal_form' => $form->value, 'holders' => $record->shareholdings->count()],
            );

            return;
        }

        $this->addCheck($batch, $checks, LegalCheckType::LegalFormCompatibility, CheckStatus::Passed, CheckSeverity::Low,
            'Forme sociale et structure des associés cohérentes',
            sprintf('Forme enregistrée : %1$s.', $record->presentationLabel()),
            ['legal_form' => $form->value],
        );
    }

    /**
     * @param  list<LegalCheckDraft>  $checks
     */
    private function checkAuscgieCompliance(
        string $batch,
        array &$checks,
        bool $capitalEntry,
        ?CompanyLegalRecord $record,
    ): void {
        if ($record === null) {
            if ($capitalEntry) {
                $this->addCheck($batch, $checks, LegalCheckType::AuscgieCompliance, CheckStatus::NeedsReview, CheckSeverity::Medium,
                    'Forme sociale à établir avant entrée au capital',
                    'Aucune fiche juridique courante n\'existe : la conformité de la forme sociétaire au mécanisme (AUSCGIE) ne peut pas être vérifiée. Une analyse humaine complète reste requise.',
                );
            }

            return;
        }

        $form = $record->form();

        if ($form->baseForm() === null) {
            if ($capitalEntry) {
                $this->addCheck($batch, $checks, LegalCheckType::AuscgieCompliance, CheckStatus::NeedsReview, CheckSeverity::Medium,
                    'Forme sociale non prise en charge par le moteur',
                    sprintf('La forme enregistrée (%1$s) n\'appartient pas aux formes types OHADA prises en charge : la conformité AUSCGIE suppose un examen humain du texte de constitution.', $form->label()),
                );
            }

            return;
        }

        $holders = $record->shareholdings->count();

        if ($form->baseForm() === LegalForm::Sarl && $holders > 50) {
            $this->addCheck($batch, $checks, LegalCheckType::AuscgieCompliance, CheckStatus::Failed, CheckSeverity::High,
                'SARL au-delà du plafond d\'associés',
                sprintf(
                    '%1$d associés sont enregistrés : une SARL ne peut pas compter plus de 50 associés (AUSCGIE). Vérifier la fiche juridique ou la forme réellement recherchée.',
                    $holders,
                ),
                ['holders' => $holders],
            );

            return;
        }

        if ($capitalEntry) {
            $this->addCheck($batch, $checks, LegalCheckType::AuscgieCompliance, CheckStatus::Passed, CheckSeverity::Low,
                'Forme sociétaire compatible',
                sprintf('La forme %1$s peut accueillir le mécanisme opéré, sous réserve des formalités correspondantes (délibérations, enregistrement, dépôt).', $form->label()),
            );
        }
    }

    /**
     * @param  list<LegalCheckDraft>  $checks
     * @param  Collection<int, ContractFinancingComponent>  $components
     * @param  Collection<int, ContractDocumentType>  $documentTypes
     */
    private function checkSecurityLawCompliance(
        string $batch,
        array &$checks,
        Collection $components,
        Collection $documentTypes,
    ): void {
        $securedInstruments = $components->filter(
            fn (ContractFinancingComponent $component) => $component->type === FinancingComponentType::Debt
                || $component->type === FinancingComponentType::Convertible,
        );

        if ($securedInstruments->isEmpty()) {
            $this->addCheck($batch, $checks, LegalCheckType::SecurityLawCompliance, CheckStatus::Passed, CheckSeverity::Low,
                'Aucune sûreté en jeu',
                'Aucun instrument de dette ou convertible n\'est opéré : le droit des sûretés n\'est pas concerné par la structure actuelle.',
            );

            return;
        }

        if ($documentTypes->contains(ContractDocumentType::SecurityDocument)) {
            $this->addCheck($batch, $checks, LegalCheckType::SecurityLawCompliance, CheckStatus::Passed, CheckSeverity::Low,
                'Acte de garantie enregistré',
                'Un acte de garantie figure dans la documentation de l\'opération : la nature et les formalités des sûretés opérées (droit OHADA des sûretés) restent à apprécier par un professionnel.',
            );

            return;
        }

        $this->addCheck($batch, $checks, LegalCheckType::SecurityLawCompliance, CheckStatus::NeedsReview, CheckSeverity::Medium,
            'Sûretés à formaliser',
            'Des instruments de dette ou des convertibles sont opérés sans qu\'aucun acte de garantie ne soit enregistré : si les parties conviennent d\'une dette non garantie, le préciser explicitement ; sinon documenter les sûretés prévues.',
        );
    }

    /**
     * @param  list<LegalCheckDraft>  $checks
     */
    private function checkStatutesCoherence(string $batch, array &$checks, ?CompanyLegalRecord $record): void
    {
        if ($record === null) {
            $this->addCheck($batch, $checks, LegalCheckType::StatutesCoherence, CheckStatus::NeedsReview, CheckSeverity::Medium,
                'Statuts non vérifiables',
                'L\'entrée au capital ne peut pas être confrontée aux statuts : aucune fiche juridique courante n\'est rattachée. Enregistrer la fiche ou fournir les statuts.',
            );

            return;
        }

        $missing = [];

        if ($record->statutes_date === null && $record->statutes_version === null) {
            $missing[] = 'référence datée des statuts';
        }

        if ($record->share_count === null) {
            $missing[] = 'nombre de parts / actions';
        }

        if ($record->nominal_value_minor === null) {
            $missing[] = 'montant nominal par titre';
        }

        if ($missing !== []) {
            $this->addCheck($batch, $checks, LegalCheckType::StatutesCoherence, CheckStatus::NeedsReview, CheckSeverity::Medium,
                'Statuts incomplets pour l\'entrée au capital',
                sprintf(
                    'Données manquantes pour confondre le mouvement de capital avec les statuts : %1$s. Aucune valeur n\'est déduite à leur place.',
                    implode(', ', $missing),
                ),
                ['legal_form' => $record->form()->value],
            );

            return;
        }

        $this->addCheck($batch, $checks, LegalCheckType::StatutesCoherence, CheckStatus::Passed, CheckSeverity::Low,
            'Statuts référencés et exploitables',
            sprintf(
                'Les statuts (référence enregistrée : %1$s) portent le nombre de titres et le nominal nécessaires au calcul de dilution.',
                $record->statutes_date instanceof \DateTimeInterface ? $record->statutes_date->format('d/m/Y') : ($record->statutes_version ?? 'non datée'),
            ),
        );
    }

    /**
     * @param  list<LegalCheckDraft>  $checks
     */
    private function checkOrganCompetence(string $batch, array &$checks, ?CompanyLegalRecord $record): void
    {
        if ($record === null || ($record->representative_user_id === null && $record->representative_name === null)) {
            $this->addCheck($batch, $checks, LegalCheckType::OrganCompetence, CheckStatus::NeedsReview, CheckSeverity::Medium,
                'Représentant légal non identifié',
                'La décision sociale exige l\'identification de l\'organe compétent (gérant, président du conseil, président SAS...) : aucun représentant n\'est enregistré sur la fiche juridique. Préciser qui signe.',
            );

            return;
        }

        $this->addCheck($batch, $checks, LegalCheckType::OrganCompetence, CheckStatus::Passed, CheckSeverity::Low,
            'Organe compétent identifié',
            sprintf(
                'Représentant identifié : %1$s (%2$s). Son habilitation reste à confirmer au regard des statuts en revue juridique.',
                $record->representative_name ?? 'utilisateur rattaché',
                $record->representative_role ?? 'rôle non précisé',
            ),
            ['representative_user_id' => $record->representative_user_id],
        );
    }

    /**
     * @param  list<LegalCheckDraft>  $checks
     */
    private function checkTitleMechanismValidity(
        string $batch,
        array &$checks,
        bool $capitalEntry,
        ?CompanyLegalRecord $record,
    ): void {
        if ($record === null) {
            return; // l'absence de fiche est déjà signalée par le contrôle de compatibilité de la forme
        }

        $holders = $record->shareholdings;

        if ($holders->count() === 0) {
            if ($capitalEntry) {
                $this->addCheck($batch, $checks, LegalCheckType::TitleMechanismValidity, CheckStatus::NeedsReview, CheckSeverity::Medium,
                    'Structure de détention du capital non renseignée',
                    'Aucun associé n\'est enregistré : la validité du mécanisme de titres (souscription, cession) ne peut pas s\'appuyer sur une structure réelle. Enregistrer les associés via la fiche juridique.',
                );
            }

            return;
        }

        $recorded = (int) $holders->sum(fn ($holder) => $holder->share_count ?? 0);

        if ($record->share_count === null) {
            $this->addCheck($batch, $checks, LegalCheckType::TitleMechanismValidity, CheckStatus::NeedsReview, CheckSeverity::Medium,
                'Nombre de titres non enregistré',
                sprintf(
                    '%1$d associé(s) totalisent %2$d titre(s) mais la fiche juridique ne porte aucun nombre total : aucun rapprochement n\'est possible sans invention.',
                    $holders->count(), $recorded,
                ),
            );

            return;
        }

        if ($recorded !== $record->share_count) {
            $this->addCheck($batch, $checks, LegalCheckType::TitleMechanismValidity, CheckStatus::Failed, CheckSeverity::High,
                'Capital incohérent avec les associés',
                sprintf(
                    'La fiche juridique déclare %1$d titre(s) mais les %2$d associé(s) enregistrés totalisent %3$d titre(s) : corriger la fiche ou la structure de détention avant tout mouvement de titres.',
                    $record->share_count, $holders->count(), $recorded,
                ),
                ['share_count' => $record->share_count, 'holdings_sum' => $recorded],
            );

            return;
        }

        $this->addCheck($batch, $checks, LegalCheckType::TitleMechanismValidity, CheckStatus::Passed, CheckSeverity::Low,
            'Capital cohérent avec la structure de détention',
            sprintf('%1$d titre(s) déclarés, %2$d détenus par les associés enregistrés.', $record->share_count, $recorded),
        );
    }

    /**
     * @param  list<LegalCheckDraft>  $checks
     * @param  Collection<int, ContractDocumentType>  $documentTypes
     */
    private function checkCorporateDecisions(
        string $batch,
        array &$checks,
        bool $decisionNeeded,
        Collection $documentTypes,
    ): void {
        if (! $decisionNeeded) {
            $this->addCheck($batch, $checks, LegalCheckType::CorporateDecisionsExistence, CheckStatus::Passed, CheckSeverity::Low,
                'Aucune décision sociale requise',
                'L\'opération ne comporte ni mouvement de capital ni mécanisme identifié : aucune délibération sociétaire n\'est attendue à ce stade.',
            );

            return;
        }

        if ($documentTypes->contains(ContractDocumentType::CompanyDecision)) {
            $this->addCheck($batch, $checks, LegalCheckType::CorporateDecisionsExistence, CheckStatus::Passed, CheckSeverity::Low,
                'Décision sociale documentée',
                'Un document « Décision sociale » figure dans l\'opération — son contenu (associé unique ou assemblée, organe signataire) reste à vérifier par la revue juridique.',
            );

            return;
        }

        $this->addCheck($batch, $checks, LegalCheckType::CorporateDecisionsExistence, CheckStatus::Failed, CheckSeverity::Critical,
            'Décision sociale manquante',
            'Un mouvement de capital est prévu mais aucun document « Décision sociale » n\'est enregistré : un associé unique (SARLU / SASU) décide seul, une SARL / SAS délibère en assemblée (AUSCGIE). Générer ou déposer la décision sociale avant la finalisation.',
            ['expected_document' => ContractDocumentType::CompanyDecision->value],
        );
    }

    /**
     * @param  list<LegalCheckDraft>  $checks
     * @param  Collection<int, ContractDocumentType>  $documentTypes
     */
    private function checkFormalities(
        string $batch,
        array &$checks,
        bool $subscriptionNeeded,
        bool $transferNeeded,
        Collection $documentTypes,
        ?CompanyLegalRecord $record,
    ): void {
        $missing = [];

        if ($subscriptionNeeded && ! $documentTypes->contains(ContractDocumentType::SubscriptionDeed)) {
            $missing[] = 'acte de souscription';
        }

        if ($transferNeeded && ! $documentTypes->contains(ContractDocumentType::TitleTransferDeed)) {
            $missing[] = 'acte de cession de titres';
        }

        if ($missing !== []) {
            $this->addCheck($batch, $checks, LegalCheckType::FormalitiesCompleteness, CheckStatus::Failed, CheckSeverity::Critical,
                'Acte(s) constitutif(s) manquant(s)',
                sprintf(
                    'Le mouvement de titres opéré exige l\'enregistrement d\'un(e) %1$s pour opposer le mouvement à la société et aux tiers (AUSCGIE) : aucun document correspondant n\'est déposé. Générer ou déposer l\'acte avant la finalisation.',
                    implode(' et un(e) ', $missing),
                ),
                ['missing' => $missing],
            );
        }

        if ($record !== null
            && $record->sectorial_authorizations !== null
            && collect(is_array($record->sectorial_authorizations) ? $record->sectorial_authorizations : [])
                ->filter(fn ($entry) => $entry !== null)->count() === 0
        ) {
            $this->addCheck($batch, $checks, LegalCheckType::FormalitiesCompleteness, CheckStatus::NeedsReview, CheckSeverity::Medium,
                'Autorisations sectorielles non documentées',
                'Aucune autorisation administrative ou sectorielle n\'est enregistrée sur la fiche : si le secteur d\'activité en requiert une (agrément, licence, inscription), la convoquer explicitement — aucune n\'est présumée.',
            );
        } elseif ($record !== null && $record->sectorial_authorizations === null) {
            $this->addCheck($batch, $checks, LegalCheckType::FormalitiesCompleteness, CheckStatus::NeedsReview, CheckSeverity::Medium,
                'Autorisations sectorielles non renseignées',
                'La fiche juridique ne précise pas si des autorisations sectorielles existent : si le secteur d\'activité en requiert une (agrément, licence, inscription), la documenter — au besoin indiquer qu\'aucune n\'est applicable.',
            );
        }

        if ($missing === [] && ($subscriptionNeeded || $transferNeeded)) {
            $this->addCheck($batch, $checks, LegalCheckType::FormalitiesCompleteness, CheckStatus::Passed, CheckSeverity::Low,
                'Actes constitutifs de titres enregistrés',
                'Les actes attendus pour le mécanisme opéré figurent dans la documentation de l\'opération.',
            );
        }
    }

    /**
     * @param  list<LegalCheckDraft>  $checks
     */
    private function checkRdcFinancialRegulation(string $batch, array &$checks, InvestmentContractWorkspace $workspace): void
    {
        $country = $workspace->company?->country_code;

        if ($country !== null && $country !== 'CD') {
            $this->addCheck($batch, $checks, LegalCheckType::RdcFinancialRegulation, CheckStatus::NeedsReview, CheckSeverity::Medium,
                'Juridiction hors périmètre moteur',
                sprintf('L\'entreprise est rattachée au code pays « %1$s » : le moteur est calibré pour l\'OHADA — RDC. Le cadre réglementaire applicable (change, licences) suppose un examen humain.', $country),
                ['country_code' => $country],
            );

            return;
        }

        $this->addCheck($batch, $checks, LegalCheckType::RdcFinancialRegulation, CheckStatus::NeedsReview, CheckSeverity::Medium,
            'Réglementation financière et de change RDC',
            'Le flux des fonds de l\'opération (réception, change, transferts via l\'extérieur) s\'apprécie au regard de la réglementation financière congolaise (BCC). La plateforme ne délivre aucun avis réglementaire : une vérification humaine reste requise avant décaissement.',
            ['country_code' => $country ?? 'CD'],
        );
    }

    /**
     * @param  list<LegalCheckDraft>  $checks
     */
    private function checkBeneficialOwners(string $batch, array &$checks, CompanyLegalRecord $record): void
    {
        $owners = collect(is_array($record->beneficial_owners) ? $record->beneficial_owners : [])
            ->filter(fn ($owner) => $owner !== null)->values();

        if ($owners->count() === 0) {
            $this->addCheck($batch, $checks, LegalCheckType::ForeignInvestorRestrictions, CheckStatus::NeedsReview, CheckSeverity::Medium,
                'Bénéficiaires effectifs non documentés',
                'Les bénéficiaires effectifs de l\'entreprise ne sont pas enregistrés sur la fiche juridique : information nécessaire à l\'appréciation d\'éventuelles restrictions (sectorielles ou liées aux personnes investies). Aucune restriction n\'est supposée s\'appliquer.',
            );

            return;
        }

        $this->addCheck($batch, $checks, LegalCheckType::ForeignInvestorRestrictions, CheckStatus::Passed, CheckSeverity::Low,
            'Bénéficiaires effectifs enregistrés',
            sprintf(
                '%1$d bénéficiaire(s) effectif(s) figure(nt) sur la fiche juridique : l\'appréciation des restrictions (secteur, régime de participation des étrangers) reste à conduire en revue humaine.',
                $owners->count(),
            ),
            ['beneficial_owners_count' => $owners->count()],
        );
    }

    /**
     * @param  list<LegalCheckDraft>  $checks
     * @param  Collection<int, ContractFinancingComponent>  $components
     */
    private function checkConvertibility(
        string $batch,
        array &$checks,
        Collection $components,
        ?CompanyLegalRecord $record,
    ): void {
        $convertibles = $components->filter(
            fn (ContractFinancingComponent $component) => $component->type === FinancingComponentType::Convertible,
        );

        foreach ($convertibles as $component) {
            $label = $component->is_primary ? 'Instrument convertible principal' : 'Tranche convertible #'.$component->id;
            $references = ['component_id' => $component->id];

            if (($component->terms['converts_to_equity'] ?? null) !== true) {
                $this->addCheck($batch, $checks, LegalCheckType::InstrumentConvertibility, CheckStatus::NeedsReview, CheckSeverity::High,
                    'Base de conversion non établie',
                    sprintf('%1$s : la conversion en capital n\'est pas actée (déclencheur, base de calcul, traitement du défaut de conversion). Tant que ces éléments manquent, l\'instrument reste convertible « sur le papier » uniquement.', $label),
                    $references,
                );

                continue;
            }

            if ($record === null || $record->share_count === null) {
                $this->addCheck($batch, $checks, LegalCheckType::InstrumentConvertibility, CheckStatus::NeedsReview, CheckSeverity::Medium,
                    'Base de titres manquante pour la conversion',
                    sprintf('%1$s : la conversion est prévue mais la fiche juridique ne porte pas la structure de capital convertible (nombre de titres). Aucune conversion ne se calcule sans invention.', $label),
                    $references,
                );

                continue;
            }

            $this->addCheck($batch, $checks, LegalCheckType::InstrumentConvertibility, CheckStatus::Passed, CheckSeverity::Low,
                'Instrument convertible documenté',
                sprintf('%1$s : conversion en capital actée sur la base d\'une structure de %2$d titre(s).', $label, $record->share_count),
                $references,
            );
        }
    }

    /**
     * Ajoute un contrôle brouillon : sévérité, statut, message
     * explicatif compréhensible et références émises par le
     * moteur — la résolution humaine reste une action horodatée
     * distincte.
     *
     * @param  list<LegalCheckDraft>  $checks
     * @param  array<string, mixed>  $details
     * @param  array<string, mixed>  $references
     */
    private function addCheck(
        string $batch,
        array &$checks,
        LegalCheckType $type,
        CheckStatus $status,
        CheckSeverity $severity,
        string $title,
        string $message,
        array $details = [],
        array $references = [],
    ): void {
        $checks[] = new LegalCheckDraft(
            type: $type,
            severity: $severity,
            status: $status,
            title: $title,
            message: $message,
            details: $details,
            references: $references,
        );
    }
}
