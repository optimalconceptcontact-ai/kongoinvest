<?php

namespace App\Contracts;

use App\Enums\FundingType;
use App\Models\DealRoom;
use App\Services\Ai\ContractDraftResult;

/**
 * Same "interface first, Null provider default" architecture as
 * ProjectAnalysisProviderInterface — React never talks to an AI provider
 * directly (spec §56). draft() returning null means "no draft could be
 * produced," never a fabricated fallback (spec §148's spirit, applied to
 * legal terms instead of an investment score).
 */
interface ContractDraftProviderInterface
{
    public function draft(DealRoom $dealRoom, FundingType $investmentType, string $conversationTranscript): ?ContractDraftResult;
}
