<?php

namespace App\Contracts;

use App\Models\InvestmentContractWorkspace;
use App\Services\Ai\NegotiationAnalysisResult;

/**
 * Same "interface first, Null provider default" architecture as
 * ContractDraftProviderInterface — React never talks to an AI provider
 * directly (spec §56). analyze() returning null means "no analysis
 * could be produced", never a fabricated synthesis.
 */
interface NegotiationAnalysisProviderInterface
{
    /**
     * @param  string  $conversationTranscript  transcript désinfecté, indexé par messages
     * @param  string  $clauseCatalog  catalogue digest de contract_clause_library (actives uniquement)
     * @param  string  $operationSummary  composantes financières + structure juridique connue
     */
    public function analyze(
        InvestmentContractWorkspace $workspace,
        string $conversationTranscript,
        string $clauseCatalog,
        string $operationSummary,
    ): ?NegotiationAnalysisResult;
}
