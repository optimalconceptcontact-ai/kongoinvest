<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    protected $fillable = [
        'name', 'email', 'password', 'phone', 'country_code', 'city',
        'locale', 'timezone', 'website', 'social_links',
        'show_email', 'show_phone', 'show_website', 'show_socials', 'show_address',
    ];

    protected $hidden = [
        'password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'social_links' => 'array',
            'show_email' => 'boolean',
            'show_phone' => 'boolean',
            'show_website' => 'boolean',
            'show_socials' => 'boolean',
            'show_address' => 'boolean',
            'last_active_at' => 'datetime',
        ];
    }

    public function entrepreneurProfile(): HasOne
    {
        return $this->hasOne(EntrepreneurProfile::class);
    }

    public function investorProfile(): HasOne
    {
        return $this->hasOne(InvestorProfile::class);
    }

    // No professionalProfile() relation and no ProfessionalProfile model
    // — "Professional" is a SubscriptionPlanKey, not a role, and never
    // had its own profile shape distinct from Entrepreneur/Investor.

    /**
     * Spec §12: an entrepreneur's own projects, always scoped this way —
     * `auth()->user()->projects()`, never Project::all() filtered in
     * React.
     */
    public function projects(): HasMany
    {
        return $this->hasMany(\App\Models\Project::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(\App\Models\Subscription::class);
    }

    public function kycProfile(): HasOne
    {
        return $this->hasOne(\App\Models\KycProfile::class);
    }

    public function isEntrepreneur(): bool
    {
        return $this->hasRole(UserRole::Entrepreneur->value);
    }

    public function isInvestor(): bool
    {
        return $this->hasRole(UserRole::Investor->value);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(UserRole::Admin->value);
    }

    /**
     * The role-specific profile for whichever role this user actually
     * has. Never used for authorization — only for convenience when
     * rendering a settings page or Inertia prop.
     */
    public function activeProfile(): EntrepreneurProfile|InvestorProfile|null
    {
        return match (true) {
            $this->isEntrepreneur() => $this->entrepreneurProfile,
            $this->isInvestor() => $this->investorProfile,
            default => null,
        };
    }
}
