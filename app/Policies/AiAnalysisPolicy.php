<?php

namespace App\Policies;

use App\Models\AiAnalysis;
use App\Models\User;

class AiAnalysisPolicy
{
    /**
     * Spec §50: available directly from Project Detail — any
     * authenticated user may view/request an analysis of a project they
     * can already see (visibility is governed by ProjectPolicy, checked
     * separately in the controller). §111's note about basic-vs-advanced
     * analysis tiers gated by subscription isn't modeled yet — every
     * analysis today is the same depth for everyone who can reach it.
     */
    public function view(User $user, AiAnalysis $analysis): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }
}
