<?php

namespace App\Models;

use App\Enums\SubscriptionFeature;
use App\Enums\UserRole;
use Database\Factories\SubscriptionPlanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    /** @use HasFactory<SubscriptionPlanFactory> */
    use HasFactory;

    protected $fillable = [
        'key', 'name', 'role_scope', 'price_minor', 'annual_price_minor', 'currency', 'billing_period',
        'limits', 'features', 'is_active', 'order', 'stripe_price_id', 'annual_stripe_price_id',
    ];

    protected function casts(): array
    {
        return [
            'role_scope' => UserRole::class,
            'limits' => 'array',
            'features' => 'array',
            'is_active' => 'boolean',
            'price_minor' => 'integer',
            'annual_price_minor' => 'integer',
        ];
    }

    public function priceMinorFor(string $billingPeriod): ?int
    {
        return $billingPeriod === 'annual' ? $this->annual_price_minor : $this->price_minor;
    }

    public function stripePriceIdFor(string $billingPeriod): ?string
    {
        return $billingPeriod === 'annual' ? $this->annual_stripe_price_id : $this->stripe_price_id;
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function hasFeature(SubscriptionFeature $feature): bool
    {
        return in_array($feature->value, $this->features ?? [], strict: true);
    }

    public function limit(string $key): ?int
    {
        return $this->limits[$key] ?? null;
    }

    /** True if any role may pick this plan (only Free, by design). */
    public function isRoleAgnostic(): bool
    {
        return $this->role_scope === null;
    }

    public function availableTo(UserRole $role): bool
    {
        return $this->isRoleAgnostic() || $this->role_scope === $role;
    }
}
