<?php

namespace App\Services\Ai\Providers;

use App\Contracts\NegotiationAnalysisProviderInterface;
use App\Models\InvestmentContractWorkspace;
use App\Services\Ai\NegotiationAnalysisResult;

/**
 * Feature switch OFF or no provider key: analyze() returns null and
 * the service degrades to a failed snapshot the parties can retry
 * from — never a fabricated analysis (spec §113).
 */
class NullNegotiationAnalysisProvider implements NegotiationAnalysisProviderInterface
{
    public function analyze(
        InvestmentContractWorkspace $workspace,
        string $conversationTranscript,
        string $clauseCatalog,
        string $operationSummary,
    ): ?NegotiationAnalysisResult {
        return null;
    }
}
