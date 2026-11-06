<?php

namespace App\Services\Contracts;

use App\Enums\CheckSeverity;
use App\Enums\CheckStatus;
use App\Enums\FinancialCheckType;
use App\Enums\FinancingComponentType;
use App\Models\ContractFinancingComponent;
use App\Models\FinancialConsistencyCheck;
use App\Models\InvestmentContractWorkspace;
use App\Services\Contracts\Data\ConsistencyRunResult;
use Illuminate\Support\Str;

/**
 * Contrôles de cohérence financière d'une opération (prompt §10
 * — Contrôles financiers). Déterministes et testables — aucun
 * calcul n'est délégué à l'IA. Chaque exécution écrit un batch
 * consultable ; une relance crée un nouveau batch sans effacer
 * l'historique.
 *
 * Niveau des alertes (prompt §10) :
 * - Critique : fige la finalisation (montants/faux pourcentages/
 *   valorisations incohérentes, double comptage dette+capital).
 * - Élevé : correction requise (intérieur d'un échéancier).
 * - Moyen : clarification ou données manquantes.
 * - Faible : recommandation.
 */
class FinancialConsistencyService
{
    public function __construct(
        private readonly CapTableCalculationService $capTable,
    ) {}

    /**
     * Exécute l'ensemble des contrôles et écrit un batch. Un
     * batch : identifiant de relance, jamais un effacement des
     * contrôles précédents (historique auditable).
     */
    public function run(InvestmentContractWorkspace $workspace): ConsistencyRunResult
    {
        $batch = 'fin-'.Str::uuid()->toString();
        $checks = [];

        $components = $workspace->financingComponents;
        $debt = $components->filter(fn ($component) => $component->type === FinancingComponentType::Debt);
        $capitalAmount = (int) $components
            ->filter(fn ($component) => $component->type === FinancingComponentType::Capital)
            ->sum('amount_minor');
        $totalAmount = (int) $components->sum('amount_minor');
        $postMoney = $workspace->post_money_minor;
        $preMoney = $workspace->pre_money_minor;

        // --- Cohérence des montants ------------------------------------
        if ($components->isEmpty()) {
            $this->addCheck($batch, $checks, FinancialCheckType::AmountConsistency, CheckStatus::NeedsReview, CheckSeverity::Medium,
                'Aucune composante de financement',
                'Aucune composante financière n’a été enregistrée pour cette opération : le montant global et la répartition capital / dette restent à établir.',
            );
        } elseif ($workspace->total_investment_minor === null) {
            $this->addCheck($batch, $checks, FinancialCheckType::AmountConsistency, CheckStatus::NeedsReview, CheckSeverity::Medium,
                'Montant global non consolidé',
                'Le montant global de l’opération n’est pas fixé. Composantes enregistrées : '.number_format($totalAmount / 100, 2, '.', ' ').' '.$workspace->currency.'.',
                ['components_total_minor' => $totalAmount],
            );
        } elseif ($workspace->total_investment_minor !== $totalAmount) {
            $this->addCheck($batch, $checks, FinancialCheckType::AmountConsistency, CheckStatus::Failed, CheckSeverity::Critical,
                'Montant global incohérent',
                sprintf(
                    'Le montant global (%1$s minor) ne correspond pas à la somme des composantes (%2$s minor, dont %3$s minor en capital et %4$s minor en dette). Corriger le montant ou les composantes avant toute finalisation.',
                    $workspace->total_investment_minor,
                    $totalAmount,
                    $capitalAmount,
                    $totalAmount - $capitalAmount,
                ),
                ['global_minor' => $workspace->total_investment_minor, 'components_total_minor' => $totalAmount],
            );
        } else {
            $this->addCheck($batch, $checks, FinancialCheckType::AmountConsistency, CheckStatus::Passed, CheckSeverity::Low,
                'Montant cohérent',
                'Les composantes du financement totalisent exactement le montant global de l’opération.',
            );
        }

        // --- Cohérence des pourcentages --------------------------------
        $this->checkPercentages($batch, $checks, $workspace);

        // --- Cohérence des valorisations (strictement hors dette) ------
        if ($capitalAmount === 0) {
            if ($preMoney !== null || $postMoney !== null) {
                $this->addCheck($batch, $checks, FinancialCheckType::ValuationConsistency, CheckStatus::NeedsReview, CheckSeverity::Medium,
                    'Valorisations fixées sans instrument en capital',
                    'Des valorisations ont été saisies alors qu’aucune composante en capital n’est enregistrée : préciser la nature de l’opération (dette pure ou instrument donnant accès au capital).',
                );
            }
        } elseif ($preMoney === null || $postMoney === null) {
            $this->addCheck($batch, $checks, FinancialCheckType::ValuationConsistency, CheckStatus::NeedsReview, CheckSeverity::Medium,
                'Valorisations non fixées',
                'L’opération comporte une composante en capital mais la valorisation pré-money et la valorisation post-money ne sont pas fixées : elles déterminent directement la participation.',
            );
        } elseif ($postMoney !== $preMoney + $capitalAmount) {
            $this->addCheck($batch, $checks, FinancialCheckType::ValuationConsistency, CheckStatus::Failed, CheckSeverity::Critical,
                'Valorisation post-money incohérente',
                sprintf(
                    'Post-money (%1$s minor) ≠ pré-money (%2$s minor) + capital (%3$s minor). La dette ne fait JAMAIS partie des fonds propres : vérifier quelle composante est comptée dans la valorisation.',
                    $postMoney, $preMoney, $capitalAmount,
                ),
                ['post_money_minor' => $postMoney, 'pre_money_minor' => $preMoney, 'capital_minor' => $capitalAmount],
            );
        } else {
            $this->addCheck($batch, $checks, FinancialCheckType::ValuationConsistency, CheckStatus::Passed, CheckSeverity::Low,
                'Valorisations cohérentes',
                'Post-money = pré-money + composante en capital ; la dette n’affecte pas la valorisation.',
            );
        }

        // --- Calcul de dilution (via CapTableCalculationService) -------
        $this->checkDilution($batch, $checks, $workspace);

        // --- Dette : intérets, échéancier, garanties -------------------
        if ($debt->isNotEmpty()) {
            $this->checkDoubleCounting($batch, $checks, $postMoney, $preMoney, $capitalAmount);
            $this->checkDebt($batch, $checks, $workspace, $debt);
        }

        // --- Capacité de remboursement et DSCR : données manquantes ----
        // La plateforme ne détient pas d'états financiers audités :
        // on signale l'incertitude au lieu d'inventer un ratio
        // (prompt §16, règle 12).
        if ($debt->isNotEmpty()) {
            $this->addCheck($batch, $checks, FinancialCheckType::RepaymentCapacity, CheckStatus::NeedsReview, CheckSeverity::Medium,
                'Capacité de remboursement à documenter',
                'Le niveau d’endettement doit être confronté aux états financiers vérifiés de l’entreprise. La plateforme ne dispose pas de données financières exploitablement calculées : une analyse humaine (expert-comptable / juriste) est requise avant signature.',
                ['references' => 'documents financiers du dossier'],
            );

            $this->addCheck($batch, $checks, FinancialCheckType::DscrRatio, CheckStatus::NeedsReview, CheckSeverity::Medium,
                'Ratio DSCR non calculable avec les données actuelles',
                'Le ratio de service de la dette exige des flux trésorerie certifiés. Aucune donnée fiable n’est disponible sur la plateforme : calcul à faire réaliser par un professionnel.',
            );
        }

        foreach ($checks as $check) {
            FinancialConsistencyCheck::forceCreate([
                'investment_contract_workspace_id' => $workspace->id,
                'batch' => $batch,
                'type' => $check['type']->value,
                'severity' => $check['severity']->value,
                'status' => $check['status']->value,
                'title' => $check['title'],
                'message' => $check['message'],
                'details' => $check['details'],
                'references' => $check['references'],
            ]);
        }

        $counts = collect($checks)->countBy(fn (array $check) => $check['status']->value)->all();
        $blocking = (bool) collect($checks)->contains(
            fn (array $check) => $check['status'] === CheckStatus::Failed && $check['severity']->blocksClosing(),
        );

        return new ConsistencyRunResult($batch, $counts, $blocking, $checks);
    }

    /**
     * Des anomalies critiques NON résolues bloquent la clôture
     * (prompt §10 : « Critique : empêche la finalisation »). La
     * porte évalue la DERNIÈRE passe uniquement : une relance
     * refait le présent ; l'historique antérieur reste consultable
     * mais jamais bloquant (même contrat que le payload React).
     */
    public function hasOpenBlockingChecks(InvestmentContractWorkspace $workspace): bool
    {
        $latestBatch = FinancialConsistencyCheck::query()
            ->where('investment_contract_workspace_id', $workspace->id)
            ->orderByDesc('id')
            ->value('batch');

        if ($latestBatch === null) {
            return false;
        }

        return FinancialConsistencyCheck::query()
            ->where('investment_contract_workspace_id', $workspace->id)
            ->where('batch', $latestBatch)
            ->where('severity', CheckSeverity::Critical->value)
            ->where('status', CheckStatus::Failed->value)
            ->whereNull('resolved_at')
            ->exists();
    }

    private function checkPercentages(string $batch, array &$checks, InvestmentContractWorkspace $workspace): void
    {
        $founder = $workspace->founder_share_bps;
        $investor = $workspace->investor_share_bps;

        if ($founder === null || $investor === null) {
            $this->addCheck($batch, $checks, FinancialCheckType::PercentageConsistency, CheckStatus::NeedsReview, CheckSeverity::Medium,
                'Répartition du capital non fixée',
                'La participation du fondateur et celle de l’investisseur ne sont pas toutes deux fixées : la répartition doit être établie avant la génération des documents définitifs.',
            );

            return;
        }

        if ($founder + $investor !== 10000) {
            $this->addCheck($batch, $checks, FinancialCheckType::PercentageConsistency, CheckStatus::Failed, CheckSeverity::Critical,
                'Pourcentages de participation incohérents',
                sprintf(
                    'Fondateur (%1$s %%) + investisseur (%2$s %%) = %3$s %% : la répartition ne totalise pas 100 %%.',
                    number_format($founder / 100, 2, '.', ''),
                    number_format($investor / 100, 2, '.', ''),
                    number_format(($founder + $investor) / 100, 2, '.', ''),
                ),
            );

            return;
        }

        $this->addCheck($batch, $checks, FinancialCheckType::PercentageConsistency, CheckStatus::Passed, CheckSeverity::Low,
            'Répartition cohérente',
            'Fondateur et investisseur totalisent 100 % du capital de l’opération.',
        );
    }

    private function checkDilution(string $batch, array &$checks, InvestmentContractWorkspace $workspace): void
    {
        $capTable = $this->capTable->calculate($workspace);

        if ($capTable === null) {
            $this->addCheck($batch, $checks, FinancialCheckType::DilutionCalculation, CheckStatus::NeedsReview, CheckSeverity::Medium,
                'Structure du capital de la société manquante',
                'La table de capitalisation ne peut pas être calculée : enregistrer la forme juridique, le nombre de parts/actions et une valorisation pré-money avec la composante en capital.',
            );

            return;
        }

        if (empty($capTable->warnings)) {
            $this->addCheck($batch, $checks, FinancialCheckType::DilutionCalculation, CheckStatus::Passed, CheckSeverity::Low,
                'Dilution calculée sans anomalie',
                sprintf(
                    '%1$d parts nouvelles émises ; investisseur %2$s %%, fondateur %3$s %%; prix unitaire %4$d minor.',
                    $capTable->newShares,
                    number_format($capTable->investorShareBps / 100, 2, '.', ''),
                    number_format($capTable->founderShareBps / 100, 2, '.', ''),
                    $capTable->pricePerShareMinor ?? 0,
                ),
            );

            return;
        }

        $targetMissed = ! $capTable->targetAchievable;

        $this->addCheck($batch, $checks, FinancialCheckType::DilutionCalculation,
            $targetMissed ? CheckStatus::Warning : CheckStatus::NeedsReview,
            $targetMissed ? CheckSeverity::High : CheckSeverity::Medium,
            $targetMissed ? 'Participation négociée non atteignable exactement' : 'Points à vérifier dans la table de capitalisation',
            'Dilution calculée avec points d’attention : '.implode(' | ', $capTable->warnings),
            ['projected' => ['investor_bps' => $capTable->investorShareBps, 'founder_bps' => $capTable->founderShareBps]],
        );
    }

    private function checkDoubleCounting(
        string $batch,
        array &$checks,
        ?int $postMoney,
        ?int $preMoney,
        int $capitalAmount,
    ): void {
        if ($postMoney === null || $preMoney === null) {
            return; // le contrôle de valorisation signale déjà les données manquantes
        }

        $surplus = $postMoney - $preMoney;

        if ($surplus > $capitalAmount) {
            $this->addCheck($batch, $checks, FinancialCheckType::DoubleCounting, CheckStatus::Failed, CheckSeverity::Critical,
                'Composante dette incluse dans la valorisation',
                sprintf(
                    'L’écart post-money − pré-money (%1$s) dépasse le capital (%2$s) de %3$s : la dette semble comptée dans les fonds propres — or elle ne dilue pas et n’affecte pas la valorisation (prompt §16, règle 5).',
                    $surplus, $capitalAmount, $surplus - $capitalAmount,
                ),
                ['surplus_minor' => $surplus, 'capital_minor' => $capitalAmount],
            );

            return;
        }

        $this->addCheck($batch, $checks, FinancialCheckType::DoubleCounting, CheckStatus::Passed, CheckSeverity::Low,
            'Aucun double comptage',
            'Les montants de dette ne sont comptés ni dans la valorisation ni dans la dilution.',
        );
    }

    private function checkDebt(string $batch, array &$checks, InvestmentContractWorkspace $workspace, $debtComponents): void
    {
        foreach ($debtComponents as $component) {
            $terms = $component->terms ?? [];
            $label = $component->is_primary ? 'Composante principale' : ('Tranche #'.$component->id);
            $references = ['component_id' => $component->id];

            // Intérêts (prompt §5.B : taux et modalités restent
            // à négocier dans le scénario de référence §7).
            $rateBps = $terms['rate_bps'] ?? null;
            $schedule = $terms['schedule'] ?? null;

            if ($rateBps === null && $schedule === null) {
                $this->addCheck($batch, $checks, FinancialCheckType::InterestCalculation, CheckStatus::NeedsReview, CheckSeverity::Medium,
                    'Taux et modalités d’intérêts à négocier',
                    sprintf('%1$s : ni le taux d’intérêt ni un échéancier n’est fixé — modalités restant à négocier entre les parties.', $label),
                    $references,
                );
            } elseif ($rateBps === null) {
                $this->addCheck($batch, $checks, FinancialCheckType::InterestCalculation, CheckStatus::NeedsReview, CheckSeverity::Medium,
                    'Taux d’intérêt non précisé',
                    sprintf('%1$s : un échéancier est proposé mais le taux d’intérêt n’est pas précisé.', $label),
                    $references,
                );
            } elseif ($schedule !== null) {
                $principal = (int) ($component->amount_minor);
                $scheduleTotal = array_sum(array_map(
                    fn (array $row) => (int) ($row['amount_minor'] ?? 0),
                    $schedule,
                ));

                if ($scheduleTotal < $principal) {
                    $this->addCheck($batch, $checks, FinancialCheckType::InterestCalculation, CheckStatus::Failed, CheckSeverity::High,
                        'Échéancier inférieur au principal remboursé',
                        sprintf(
                            '%1$s : le total des remboursements (%2$s minor) ne couvre même pas le principal (%3$s minor) — corriger l’échéancier ou le taux.',
                            $label, $scheduleTotal, $principal,
                        ),
                        $references,
                    );
                } else {
                    $interest = $scheduleTotal - $principal;
                    $this->addCheck($batch, $checks, FinancialCheckType::InterestCalculation, CheckStatus::Passed, CheckSeverity::Low,
                        'Intérêts cohérents',
                        sprintf(
                            '%1$s : total des remboursements %2$s minor, intérêts cumulés %3$s minor (%4$s points de base).',
                            $label, $scheduleTotal, $interest, $rateBps,
                        ),
                        $references,
                    );
                }
            }

            // Échéancier et différé.
            if ($schedule !== null && is_array($schedule)) {
                $rows = count($schedule);
                if ($rows > 0) {
                    $this->addCheck($batch, $checks, FinancialCheckType::ScheduleConsistency, CheckStatus::Passed, CheckSeverity::Low,
                        'Échéancier structuré',
                        sprintf('%1$s : %2$d échéance(s) enregistrée(s).', $label, $rows),
                        $references,
                    );
                }
            }

            $duration = $terms['duration_months'] ?? null;
            $deferral = $terms['deferral_months'] ?? null;

            if ($duration === null) {
                $this->addCheck($batch, $checks, FinancialCheckType::ScheduleConsistency, CheckStatus::NeedsReview, CheckSeverity::Medium,
                    'Durée de remboursement non fixée',
                    sprintf('%1$s : la durée du prêt n’est pas précisée — elle conditionne l’échéancier et l’exigibilité.', $label),
                    $references,
                );
            } elseif ($deferral !== null && $deferral >= $duration) {
                $this->addCheck($batch, $checks, FinancialCheckType::ScheduleConsistency, CheckStatus::Failed, CheckSeverity::High,
                    'Différé incohérent avec la durée',
                    sprintf('%1$s : le différé (%2$d mois) n’est pas inférieur à la durée du prêt (%3$d mois).', $label, $deferral, $duration),
                    $references,
                );
            }

            // Garanties : signaler leur absence ou une couverture
            // inférieure au principal, sans jamais la valoriser
            // arbitrairement.
            $guarantees = $terms['guarantees'] ?? [];

            if ($terms['unsecured'] ?? false) {
                $this->addCheck($batch, $checks, FinancialCheckType::CollateralCoverage, CheckStatus::Passed, CheckSeverity::Low,
                    'Dette non garantie (convenue)',
                    sprintf('%1$s : l’absence de garantie a été négociée explicitement entre les parties.', $label),
                    $references,
                );
            } elseif (empty($guarantees)) {
                $this->addCheck($batch, $checks, FinancialCheckType::CollateralCoverage, CheckStatus::NeedsReview, CheckSeverity::Medium,
                    'Aucune garantie enregistrée',
                    sprintf('%1$s : aucune garantie n’est enregistrée. Une dette non garantie reste juridiquement valable mais moins protectrice pour le prêteur — à arbitrer.', $label),
                    $references,
                );
            } else {
                $collateralTotal = array_sum(array_map(
                    fn (array $row) => (int) ($row['value_minor'] ?? 0),
                    $guarantees,
                ));

                if ($collateralTotal === 0) {
                    $this->addCheck($batch, $checks, FinancialCheckType::CollateralCoverage, CheckStatus::NeedsReview, CheckSeverity::Medium,
                        'Garanties non valorisées',
                        sprintf('%1$s : des garanties sont mentionnées mais aucune n’est chiffrée — préciser leur valeur afin d’évaluer la couverture.', $label),
                        $references,
                    );
                } else {
                    $principal = (int) ($component->amount_minor);
                    $ratio = $principal > 0 ? (int) round(($collateralTotal * 100) / $principal) : 0;

                    $this->addCheck($batch, $checks, FinancialCheckType::CollateralCoverage,
                        $ratio < 100 ? CheckStatus::Warning : CheckStatus::Passed,
                        $ratio < 100 ? CheckSeverity::High : CheckSeverity::Low,
                        $ratio < 100 ? 'Couverture des garanties inférieure au principal' : 'Garanties couvrant le principal',
                        sprintf('Couverture de %1$d %% du principal (%2$s minor de garanties pour %3$s minor de dette).', $ratio, $collateralTotal, $principal),
                        $references,
                    );
                }
            }
        }

        // Garanties se recouvrant entre composantes : sûretés
        // engagées deux fois sur des instruments renégociés.
        // Vérifié une fois par exécution, hors de la boucle.
        $duplicated = $this->duplicatedGuaranteeLabels($debtComponents);

        if ($duplicated !== []) {
            $this->addCheck($batch, $checks, FinancialCheckType::DoubleCounting, CheckStatus::Failed, CheckSeverity::High,
                'Garanties engagées dans plusieurs composantes',
                'Les mêmes garanties ('.implode(', ', $duplicated).') figurent dans plusieurs composantes de dette : vérifier les sûretés réellement consenties (droit des sûretés OHADA).',
            );
        }
    }

    /**
     * @param  iterable<ContractFinancingComponent>  $debtComponents
     * @return list<string>
     */
    private function duplicatedGuaranteeLabels(iterable $debtComponents): array
    {
        $all = collect($debtComponents)
            ->flatMap(fn ($component) => (array) ($component->terms['guarantees'] ?? []))
            ->filter(fn ($row) => is_array($row) && ($row['label'] ?? null) !== null)
            ->map(fn ($row) => $row['label'])
            ->values();

        return $all->duplicates()->values()->all();
    }

    /**
     * @param  list<array<string, mixed>>  $checks
     */
    private function addCheck(
        string $batch,
        array &$checks,
        FinancialCheckType $type,
        CheckStatus $status,
        CheckSeverity $severity,
        string $title,
        string $message,
        array $details = [],
        array $references = [],
    ): void {
        $checks[] = [
            'type' => $type,
            'severity' => $severity,
            'status' => $status,
            'title' => $title,
            'message' => $message,
            'details' => $details,
            'references' => $references,
        ];
    }
}
