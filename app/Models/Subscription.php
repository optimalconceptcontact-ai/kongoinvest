<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    /** @use HasFactory<\Database\Factories\SubscriptionFactory> */
    use HasFactory;

    protected $fillable = [
        'subscription_plan_id', 'status', 'starts_at', 'trial_ends_at',
        'ends_at', 'canceled_at', 'payment_provider', 'provider_subscription_id',
    ];

    // `user_id` is deliberately not mass-assignable — always set via
    // $user->subscriptions()->create(), never from request input.

    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'starts_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'ends_at' => 'datetime',
            'canceled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function isCurrentlyActive(): bool
    {
        if (! in_array($this->status, [SubscriptionStatus::Active, SubscriptionStatus::Trialing], true)) {
            return false;
        }

        return $this->ends_at === null || $this->ends_at->isFuture();
    }
}
