<?php

namespace App\Policies;

use App\Models\Subscription;
use App\Models\User;

class SubscriptionPolicy
{
    public function view(User $user, Subscription $subscription): bool
    {
        return $subscription->user_id === $user->id || $user->can('subscriptions.manage');
    }

    /** Self-service plan switching — every registered role may manage their own subscription. */
    public function manage(User $user): bool
    {
        return $user->can('subscriptions.manage');
    }
}
