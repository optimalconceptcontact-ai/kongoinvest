<?php

namespace App\Services\Ai;

use App\Enums\AiInteractionType;
use App\Enums\AiRequestStatus;
use App\Models\AiUsage;
use App\Models\Project;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Validation\ValidationException;

/**
 * Spec §112: user, project, type, tokens/cost, provider, timestamp,
 * result, status — every AI call anywhere in the app writes exactly one
 * row here, success or failure. Spec §53's quota mention
 * ("ai_monthly_token_quota", already seeded per-plan in Phase 4) is
 * enforced here too — Free's low quota vs Entrepreneur/Investor's much
 * higher one is a real limit, not just a displayed number.
 */
class AiUsageService
{
    public function __construct(private SubscriptionService $subscriptions) {}

    public function assertWithinQuota(User $user): void
    {
        $quota = $this->subscriptions->limit($user, 'ai_monthly_token_quota');

        if ($quota === null) {
            return; // no quota configured for this plan — unlimited
        }

        $usedThisMonth = AiUsage::where('user_id', $user->id)
            ->where('status', AiRequestStatus::Completed->value)
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('tokens_used');

        if ($usedThisMonth >= $quota) {
            throw ValidationException::withMessages([
                'ai' => "Vous avez atteint votre quota mensuel d'utilisation de l'IA pour votre plan. Passez à un plan supérieur pour continuer.",
            ]);
        }
    }

    public function record(User $user, AiInteractionType $type, AiRequestStatus $status, ?Project $project = null, ?string $provider = null, int $tokensUsed = 0): AiUsage
    {
        return AiUsage::create([
            'user_id' => $user->id,
            'project_id' => $project?->id,
            'type' => $type->value,
            'provider' => $provider,
            'tokens_used' => $tokensUsed,
            'status' => $status->value,
            'created_at' => now(),
        ]);
    }
}
