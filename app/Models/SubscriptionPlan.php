<?php

namespace App\Models;

use App\Enums\SubscriptionFeature;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    /** @use HasFactory<\Database\Factories\SubscriptionPlanFactory> */
    use HasFactory;

    protected $fillable = [
        'key', 'name', 'role_scope', 'price_minor', 'currency', 'billing_period',
        'limits', 'features', 'is_active', 'order',
    ];

    protected function casts(): array
    {
        return [
            'role_scope' => UserRole::class,
            'limits' => 'array',
            'features' => 'array',
            'is_active' => 'boolean',
            'price_minor' => 'integer',
        ];
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
