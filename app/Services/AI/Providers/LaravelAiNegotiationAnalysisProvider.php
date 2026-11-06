<?php

namespace App\Services\Ai\Providers;

use App\Ai\Agents\NegotiationAnalysisAgent;
use App\Contracts\NegotiationAnalysisProviderInterface;
use App\Models\InvestmentContractWorkspace;
use App\Services\Ai\Concerns\InteractsWithAiProvider;
use App\Services\Ai\NegotiationAnalysisResult;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Responses\StructuredAgentResponse;

/**
 * The only class that talks to the model for negotiation analysis.
 * Returns null — never a fabricated synthesis — when unavailable or
 * malformed; NegotiationAnalysisService turns that into a failed
 * snapshot the parties can retry from.
 */
class LaravelAiNegotiationAnalysisProvider implements NegotiationAnalysisProviderInterface
{
    use InteractsWithAiProvider;

    public function analyze(
        InvestmentContractWorkspace $workspace,
        string $conversationTranscript,
        string $clauseCatalog,
        string $operationSummary,
    ): ?NegotiationAnalysisResult {
        if (! $this->aiAvailable()) {
            return null;
        }

        try {
            $agent = new NegotiationAnalysisAgent($workspace, $conversationTranscript, $clauseCatalog, $operationSummary);

            $response = $this->withTransientRetry(fn () => $agent->prompt(
                $agent->context(),
                provider: $this->aiProvider(),
                model: $this->aiModel(),
                timeout: $this->aiTimeout(),
            ));
        } catch (\Throwable $e) {
            Log::warning('AI negotiation analysis threw', ['message' => $e->getMessage(), 'workspace_id' => $workspace->id]);

            return null;
        }

        return $this->parse($response, $workspace);
    }

    private function parse(mixed $response, InvestmentContractWorkspace $workspace): ?NegotiationAnalysisResult
    {
        $decoded = $response instanceof StructuredAgentResponse ? $response->toArray() : [];

        $result = NegotiationAnalysisResult::fromDecoded($decoded, $this->tokensUsed($response->usage));

        if ($result === null) {
            Log::warning('AI negotiation analysis returned an unusable payload', [
                'workspace_id' => $workspace->id,
                'raw' => $response->text ?? null,
            ]);
        }

        return $result;
    }
}
