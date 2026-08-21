<?php

namespace App\Services\Ai\Providers;

use App\Contracts\ContractDraftProviderInterface;
use App\Enums\FundingType;
use App\Models\DealRoom;
use App\Services\Ai\ContractDraftResult;

class NullContractDraftProvider implements ContractDraftProviderInterface
{
    public function draft(DealRoom $dealRoom, FundingType $investmentType, string $conversationTranscript): ?ContractDraftResult
    {
        return null;
    }
}
