<?php

namespace App\Services\Contracts;

use App\Enums\DataVerificationStatus;
use App\Enums\EquityMechanism;
use App\Enums\FinancingComponentType;
use App\Models\CompanyLegalRecord;
use App\Models\CompanyShareholding;
use App\Models\InvestmentContractWorkspace;
use App\Services\Contracts\Data\CapTableResult;

/**
 * Calcul déterministe de la table de capitalisation après
 * opération (prompt §13 — CapTableCalculationService). Aucun
 * appel IA : les calculs financiers sont déterministes et
 * testables.
 *
 * Règle fondamentale (prompt §16, règle 5) : la dilution ne
 * concerne que les instruments donnant accès au capital selon
 * un mécanisme juridiquement valable. La dette n'entre JAMAIS
 * dans la valorisation ni dans la répartition du capital.
 */
class CapTableCalculationService
{
    /** Tolérance (points de base) entre participation négociée et participation exactement atteignable. */
    public const TARGET_TOLERANCE_BPS = 5;

    public function calculate(InvestmentContractWorkspace $workspace): ?CapTableResult
    {
        $company = $workspace->company;

        if ($company === null) {
            return null;
        }

        $record = CompanyLegalRecord::query()
            ->where('company_id', $company->id)
            ->where('is_current', true)
            ->orderByDesc('id')
            ->first();

        if ($record === null || $record->share_count === null || $record->share_count < 1) {
            return null;
        }

        $preMoney = $workspace->pre_money_minor;

        // Seule la composante EN CAPITAL donne accès au capital.
        // Une composante convertible ne dilue JAMAIS ici : son
        // mécanisme de conversion requiert une validation
        // juridique spécifique et n'est que signalée (§16, r.11).
        $capitalComponents = $workspace->financingComponents
            ->filter(fn ($component) => $component->type === FinancingComponentType::Capital);
        $capitalAmount = (int) $capitalComponents->sum('amount_minor');

        if ($preMoney === null || $preMoney < 1 || $capitalAmount < 1) {
            return null;
        }

        $investorUserId = $workspace->dealRoom->investor_user_id;
        $warnings = [];

        if ($capitalComponents->contains(fn ($component) => $component->equity_mechanism === null)) {
            $warnings[] = 'Mécanisme d’entrée au capital non précisé : une augmentation de capital est présupposée — à confirmer.';
        }

        $mechanism = $capitalComponents->first()->equity_mechanism ?? EquityMechanism::CapitalIncrease;

        if ($record->verification !== DataVerificationStatus::Verified) {
            $warnings[] = 'Données juridiques de la société non entièrement vérifiées : confirmer la structure du capital au regard des statuts et du RCCM.';
        }

        if ($record->capital_currency !== null && $record->capital_currency !== $workspace->currency) {
            $warnings[] = sprintf(
                'La devise du capital social (%1$s) diffère de la devise de l’opération (%2$s) : la conversion appliquée doit être précisée.',
                $record->capital_currency,
                $workspace->currency,
            );
        }

        if ($workspace->financingComponents->contains(fn ($component) => $component->type === FinancingComponentType::Convertible)) {
            $warnings[] = 'Composante convertible présente : sa dilution future n’est pas comptée ici — le mécanisme de conversion doit faire l’objet d’une validation juridique spécifique (prompt §16, règle 11).';
        }

        // Valorisation STRICTEMENT hors dette : post = pré + capital.
        $postMoney = $workspace->post_money_minor ?? ($preMoney + $capitalAmount);

        return match ($mechanism) {
            EquityMechanism::CapitalIncrease => $this->calculateCapitalIncrease(
                $record, $workspace, $investorUserId, $preMoney, $postMoney, $capitalAmount, $warnings,
            ),
            EquityMechanism::TitleTransfer => $this->calculateTitleTransfer(
                $record, $workspace, $investorUserId, $preMoney, $postMoney, $capitalAmount, $warnings,
            ),
        };
    }

    /**
     * Augmentation de capital : les parts nouvelles sont souscrites
     * par l'investisseur ; les associés existants sont dilués.
     *
     * @param  list<string>  $warnings
     */
    private function calculateCapitalIncrease(
        CompanyLegalRecord $record,
        InvestmentContractWorkspace $workspace,
        ?int $investorUserId,
        int $preMoney,
        int $postMoney,
        int $capitalAmount,
        array &$warnings,
    ): CapTableResult {
        $shareCountBefore = $record->share_count;

        // Parts nouvelles = parts existantes × capital / pré-money.
        // Arithmétique entière : le reliquat est signalé, l'arrondi
        // n'est jamais silencieux.
        $numerator = $shareCountBefore * $capitalAmount;
        $newShares = intdiv($numerator, $preMoney);
        $divisible = ($numerator % $preMoney) === 0;

        if (! $divisible) {
            $warnings[] = sprintf(
                'Le nombre de parts à émettre n’est pas un nombre entier avec la structure actuelle (%1$d parts, %2$d minor de capital, pré-money %3$d minor = %4$s parts) : un ajustement de la valeur nominale ou une division préalable des parts est nécessaire pour atteindre la participation visée.',
                $shareCountBefore,
                $capitalAmount,
                $preMoney,
                number_format($numerator / $preMoney, 4, '.', ''),
            );
        }

        $pricePerShareExact = $preMoney % $shareCountBefore === 0;
        $pricePerShare = $pricePerShareExact
            ? intdiv($preMoney, $shareCountBefore)
            : (int) round($preMoney / $shareCountBefore);

        if (! $pricePerShareExact) {
            $warnings[] = sprintf(
                'La valorisation pré-money (%1$d minor) n’est pas divisible par le nombre de parts (%2$d) : le prix unitaire est arrondi à %3$d minor — vérifier la valeur nominale.',
                $preMoney,
                $shareCountBefore,
                $pricePerShare,
            );
        }

        // Participation négociée explicite, sinon déduite de la
        // valorisation : participation = capital / post-money.
        $targetBps = $workspace->investor_share_bps
            ?? (int) round(($capitalAmount * 10000) / $postMoney);

        $shareCountAfter = $shareCountBefore + $newShares;

        $entries = [];
        foreach ($record->shareholdings as $holding) {
            $isInvestor = $investorUserId !== null && $holding->holder_user_id === $investorUserId;
            $shares = (int) ($holding->share_count ?? 0);

            $entries[] = $this->entry(
                $holding->holder_name,
                $holding->holder_type->value,
                (bool) $holding->is_founder,
                $isInvestor,
                $shares,
                $isInvestor ? $shares + $newShares : $shares, // l'associé existant qui est aussi l'acheteur absorbe les titres nouveaux
                $shareCountBefore,
                $shareCountAfter,
            );
        }

        // L'investisseur ne détenait aucun titre : entrée nouvelle.
        if ($investorUserId !== null && ! collect($entries)->contains(fn (array $entry) => $entry['is_investor'])) {
            $entries[] = [
                'holder_name' => $workspace->dealRoom->investor?->name ?? 'Investisseur',
                'holder_type' => 'individual',
                'is_founder' => false,
                'is_investor' => true,
                'shares_before' => 0,
                'shares_after' => $newShares,
                'percentage_before_bps' => 0,
                'percentage_after_bps' => (int) round(($newShares * 10000) / max($shareCountAfter, 1)),
            ];
        }

        [$investorShareBps, $founderShareBps] = $this->aggregateShares($entries);

        $targetAchievable = $divisible && abs($investorShareBps - $targetBps) <= self::TARGET_TOLERANCE_BPS;
        if (! $targetAchievable && $divisible) {
            $warnings[] = sprintf(
                'La participation effectivement obtenue (%1$s %%) diffère de la participation négociée (%2$s %%) : ajuster la valorisation, la valeur nominale ou le nombre de parts émises.',
                number_format($investorShareBps / 100, 2, '.', ''),
                number_format($targetBps / 100, 2, '.', ''),
            );
        }

        return new CapTableResult(
            mechanism: EquityMechanism::CapitalIncrease->value,
            currency: $workspace->currency,
            preMoneyMinor: $preMoney,
            postMoneyMinor: $postMoney,
            capitalAmountMinor: $capitalAmount,
            shareCountBefore: $shareCountBefore,
            newShares: $newShares,
            shareCountAfter: $shareCountAfter,
            pricePerShareMinor: $pricePerShare,
            pricePerShareExact: $pricePerShareExact,
            investorShareBps: $investorShareBps,
            founderShareBps: $founderShareBps,
            targetInvestorShareBps: $targetBps,
            targetAchievable: $targetAchievable,
            verifiedData: $record->verification === DataVerificationStatus::Verified,
            entries: $entries,
            warnings: $warnings,
        );
    }

    /**
     * Cession de titres : AUCUNE part nouvelle — un associé
     * existant cède à l'investisseur (règles AUSCGIE propres au
     * transfert des titres ; formalités distinctes de
     * l'augmentation de capital).
     *
     * @param  list<string>  $warnings
     */
    private function calculateTitleTransfer(
        CompanyLegalRecord $record,
        InvestmentContractWorkspace $workspace,
        ?int $investorUserId,
        int $preMoney,
        int $postMoney,
        int $capitalAmount,
        array &$warnings,
    ): CapTableResult {
        $shareCount = $record->share_count;

        // Valeur de référence inchangée : participation visée =
        // prix payé / valeur totale (post-money négociée, sinon
        // pré-money — la cession ne crée pas du capital frais).
        $referenceMoney = $workspace->post_money_minor ?? $preMoney;
        $targetBps = $workspace->investor_share_bps
            ?? (int) round(($capitalAmount * 10000) / $referenceMoney);

        $sharesToTransfer = intdiv($shareCount * $targetBps, 10000);
        $divisible = ($shareCount * $targetBps) % 10000 === 0;

        if (! $divisible) {
            $warnings[] = sprintf(
                'La participation visée (%1$s %%) ne correspond pas à un nombre entier de parts sur %2$d parts existantes (%3$s parts à céder) : une division préalable des parts est nécessaire.',
                number_format($targetBps / 100, 2, '.', ''),
                $shareCount,
                number_format(($shareCount * $targetBps) / 10000, 4, '.', ''),
            );
        }

        // Le cédant pressenti est l'associé le plus important qui
        // n'est pas l'investisseur (le fondateur en pratique).
        // Ce choix est rendu visible, jamais implicite.
        /** @var CompanyShareholding|null $seller */
        $seller = collect($record->shareholdings)
            ->filter(fn (CompanyShareholding $holding) => $investorUserId === null || $holding->holder_user_id !== $investorUserId)
            ->sortByDesc(fn (CompanyShareholding $holding) => $holding->share_count ?? 0)
            ->first();

        if ($seller === null) {
            $warnings[] = 'Aucun associé cédant identifiable pour la cession de titres : préciser l’identité du cédant.';
            $sharesToTransfer = 0;
            $divisible = false;
        }

        if ($seller !== null && ($seller->share_count ?? 0) < $sharesToTransfer) {
            $warnings[] = sprintf(
                'Le cédant pressenti (%1$s) ne détient que %2$d parts pour %3$d parts à céder : envisager plusieurs cédants ou revoir la participation.',
                $seller->holder_name,
                $seller->share_count,
                $sharesToTransfer,
            );
        }

        $pricePerShareExact = $preMoney % max($shareCount, 1) === 0;
        $pricePerShare = $pricePerShareExact
            ? intdiv($preMoney, $shareCount)
            : (int) round($preMoney / $shareCount);

        if (! $pricePerShareExact) {
            $warnings[] = sprintf(
                'La valorisation pré-money (%1$d minor) n’est pas divisible par le nombre de parts (%2$d) : le prix unitaire est arrondi à %3$d minor — vérifier la valeur nominale.',
                $preMoney,
                $shareCount,
                $pricePerShare,
            );
        }

        $entries = [];
        foreach ($record->shareholdings as $holding) {
            $isInvestor = $investorUserId !== null && $holding->holder_user_id === $investorUserId;
            $shares = (int) ($holding->share_count ?? 0);

            $after = $shares;
            if ($isInvestor) {
                $after += $sharesToTransfer; // détention préalable de l'investisseur
            } elseif ($seller !== null && $holding->id === $seller->id) {
                $after = max($shares - $sharesToTransfer, 0);
            }

            $entries[] = $this->entry(
                $holding->holder_name,
                $holding->holder_type->value,
                (bool) $holding->is_founder,
                $isInvestor,
                $shares,
                $after,
                $shareCount,
                $shareCount, // le total du capital est inchangé dans une cession
            );
        }

        if ($investorUserId !== null && ! collect($entries)->contains(fn (array $entry) => $entry['is_investor'])) {
            $entries[] = [
                'holder_name' => $workspace->dealRoom->investor?->name ?? 'Investisseur',
                'holder_type' => 'individual',
                'is_founder' => false,
                'is_investor' => true,
                'shares_before' => 0,
                'shares_after' => $sharesToTransfer,
                'percentage_before_bps' => 0,
                'percentage_after_bps' => (int) round(($sharesToTransfer * 10000) / max($shareCount, 1)),
            ];
        }

        [$investorShareBps, $founderShareBps] = $this->aggregateShares($entries);

        $targetAchievable = $divisible && abs($investorShareBps - $targetBps) <= self::TARGET_TOLERANCE_BPS;
        if (! $targetAchievable && $divisible) {
            $warnings[] = sprintf(
                'La participation effectivement acquise (%1$s %%) diffère de la participation négociée (%2$s %%) : ajuster la valorisation ou le nombre de parts cédées.',
                number_format($investorShareBps / 100, 2, '.', ''),
                number_format($targetBps / 100, 2, '.', ''),
            );
        }

        return new CapTableResult(
            mechanism: EquityMechanism::TitleTransfer->value,
            currency: $workspace->currency,
            preMoneyMinor: $preMoney,
            postMoneyMinor: $postMoney,
            capitalAmountMinor: $capitalAmount, // prix payé, pas capital frais
            shareCountBefore: $shareCount,
            newShares: 0,
            shareCountAfter: $shareCount,
            pricePerShareMinor: $pricePerShare,
            pricePerShareExact: $pricePerShareExact,
            investorShareBps: $investorShareBps,
            founderShareBps: $founderShareBps,
            targetInvestorShareBps: $targetBps,
            targetAchievable: $targetAchievable,
            verifiedData: $record->verification === DataVerificationStatus::Verified,
            entries: $entries,
            warnings: $warnings,
        );
    }

    /**
     * @param  list<array<string, mixed>>  $entries
     * @return array{0: int, 1: int}
     */
    private function aggregateShares(array $entries): array
    {
        $investorShareBps = collect($entries)
            ->filter(fn (array $entry) => $entry['is_investor'])
            ->sum('percentage_after_bps');

        $founderShareBps = collect($entries)
            ->filter(fn (array $entry) => $entry['is_founder'])
            ->sum('percentage_after_bps');

        return [$investorShareBps, $founderShareBps];
    }

    /**
     * @return array<string, mixed>
     */
    private function entry(
        string $holderName,
        string $holderType,
        bool $isFounder,
        bool $isInvestor,
        int $sharesBefore,
        int $sharesAfter,
        int $denominatorBefore,
        int $denominatorAfter,
    ): array {
        return [
            'holder_name' => $holderName,
            'holder_type' => $holderType,
            'is_founder' => $isFounder,
            'is_investor' => $isInvestor,
            'shares_before' => $sharesBefore,
            'shares_after' => $sharesAfter,
            'percentage_before_bps' => $denominatorBefore > 0
                ? (int) round(($sharesBefore * 10000) / $denominatorBefore)
                : 0,
            'percentage_after_bps' => $denominatorAfter > 0
                ? (int) round(($sharesAfter * 10000) / $denominatorAfter)
                : 0,
        ];
    }
}
