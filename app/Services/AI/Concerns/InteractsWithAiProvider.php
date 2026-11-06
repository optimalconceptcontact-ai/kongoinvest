<?php

namespace App\Services\Ai\Concerns;

use Closure;
use Laravel\Ai\Exceptions\ProviderConnectionException;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use Laravel\Ai\Responses\Data\Usage;

/**
 * Shared plumbing for the laravel/ai-backed provider implementations:
 * which provider/model to use, whether it is actually usable, and how to
 * flatten a Usage object into the single integer AiUsage stores.
 */
trait InteractsWithAiProvider
{
    /**
     * The configured provider name (a key of config('ai.providers')).
     */
    protected function aiProvider(): string
    {
        return config('ai.features.provider') ?: config('ai.default');
    }

    /**
     * Explicit model override, or null to let the provider pick its own
     * default — except for DeepSeek, whose laravel/ai default model name
     * (`deepseek-v4-flash`) the API rejects, so pin a real one.
     */
    protected function aiModel(): ?string
    {
        if ($model = config('ai.features.model')) {
            return $model;
        }

        return $this->aiProvider() === 'deepseek' ? 'deepseek-chat' : null;
    }

    /**
     * Per-call HTTP timeout, clamped so it can never reach PHP's
     * max_execution_time (30s on Herd). A provider that hangs past that
     * fatals the whole request with an uncatchable error; a shorter
     * timeout lets the provider throw, get caught here, and degrade to
     * the clean "retry later" state (spec §113).
     */
    protected function aiTimeout(): int
    {
        return max(5, min(28, (int) config('ai.features.timeout', 20)));
    }

    /**
     * True only when the features are switched on AND the chosen provider
     * has an API key configured. Anything else means "unavailable" — the
     * caller returns null and the feature degrades cleanly (spec §113).
     */
    protected function aiAvailable(): bool
    {
        if (! config('ai.features.enabled')) {
            return false;
        }

        return filled(config('ai.providers.'.$this->aiProvider().'.key'));
    }

    protected function tokensUsed(Usage $usage): int
    {
        return $usage->promptTokens
            + $usage->completionTokens
            + $usage->reasoningTokens;
    }

    /**
     * Run an agent call, retrying briefly on transient provider hiccups
     * (overloaded / connection dropped) — Gemini's flash tiers 503 under
     * load and that blip shouldn't reach the user as "réessayez". Rate
     * limits and out-of-credit errors are deliberately NOT retried
     * (pointless or harmful). Retries stop once there is no wall-clock
     * budget left for another full attempt before PHP's ~30s request
     * limit.
     *
     * @template T
     *
     * @param  Closure(): T  $call
     * @return T
     */
    protected function withTransientRetry(Closure $call): mixed
    {
        $start = microtime(true);
        $backoff = [1.0, 2.0]; // up to two retries

        for ($attempt = 0; ; $attempt++) {
            try {
                return $call();
            } catch (ProviderOverloadedException|ProviderConnectionException $e) {
                $wait = $backoff[$attempt] ?? null;
                $elapsed = microtime(true) - $start;

                if ($wait === null || $elapsed + $wait + $this->aiTimeout() > 27.0) {
                    throw $e;
                }

                usleep((int) ($wait * 1_000_000));
            }
        }
    }
}
