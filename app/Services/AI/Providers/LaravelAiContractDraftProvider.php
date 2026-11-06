<?php

namespace App\Services\Ai\Providers;

use App\Ai\Agents\ContractDraftAgent;
use App\Contracts\ContractDraftProviderInterface;
use App\Enums\FundingType;
use App\Models\DealRoom;
use App\Services\Ai\Concerns\InteractsWithAiProvider;
use App\Services\Ai\ContractDraftResult;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Responses\StructuredAgentResponse;

/**
 * The only class that talks to the model for contract drafting. Returns
 * null — never a fabricated fallback — when unavailable or malformed;
 * ContractDraftService turns that into a validation error the investor
 * can retry from.
 */
class LaravelAiContractDraftProvider implements ContractDraftProviderInterface
{
    use InteractsWithAiProvider;

    public function draft(DealRoom $dealRoom, FundingType $investmentType, string $conversationTranscript): ?ContractDraftResult
    {
        if (! $this->aiAvailable()) {
            return null;
        }

        try {
            $agent = new ContractDraftAgent($dealRoom, $investmentType, $conversationTranscript);

            $response = $this->withTransientRetry(fn () => $agent->prompt(
                $agent->context(),
                provider: $this->aiProvider(),
                model: $this->aiModel(),
                timeout: $this->aiTimeout(),
            ));

            return $this->parse($response, $dealRoom, $investmentType);
        } catch (\Throwable $e) {
            Log::warning('AI contract draft threw', ['message' => $e->getMessage(), 'deal_room_id' => $dealRoom->id]);

            return null;
        }
    }

    private function parse(mixed $response, DealRoom $dealRoom, FundingType $investmentType): ?ContractDraftResult
    {
        $decoded = $response instanceof StructuredAgentResponse ? $response->toArray() : [];

        if (! isset($decoded['amount_minor'], $decoded['summary'])) {
            Log::warning('AI contract draft returned an unusable payload', [
                'deal_room_id' => $dealRoom->id,
                'raw' => $response->text ?? null,
            ]);

            return null;
        }

        // Model-controlled values are re-validated and clamped against the
        // project's declared funding terms — never persisted as-is.
        return ContractDraftResult::fromDecoded(
            $decoded,
            $investmentType,
            $dealRoom->project,
            $this->tokensUsed($response->usage),
        );
    }
}
