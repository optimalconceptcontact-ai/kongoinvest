<?php

namespace App\Models;

use App\Enums\FundingType;
use App\Enums\InvestmentHorizon;
use App\Enums\InvestorType;
use App\Enums\RiskAppetite;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class InvestorProfile extends Model
{
    /** @use HasFactory<\Database\Factories\InvestorProfileFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id', 'company_id', 'country_code', 'city', 'investor_type', 'funding_type',
        'investment_horizon', 'risk_appetite', 'ticket_min_minor', 'ticket_max_minor',
        'ticket_currency', 'preferred_geographies', 'investment_thesis',
        'impact_focused', 'onboarding_completed',
    ];

    protected function casts(): array
    {
        return [
            'investor_type' => InvestorType::class,
            'funding_type' => FundingType::class,
            'investment_horizon' => InvestmentHorizon::class,
            'risk_appetite' => RiskAppetite::class,
            'preferred_geographies' => 'array',
            'impact_focused' => 'boolean',
            'onboarding_completed' => 'boolean',
            'ticket_min_minor' => 'integer',
            'ticket_max_minor' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** Spec §17 — an investor can select multiple sectors. */
    public function sectors(): BelongsToMany
    {
        // Explicit pivot name — Eloquent's default convention would be
        // investor_profile_sector, but the migration named it investor_sector.
        return $this->belongsToMany(Sector::class, 'investor_sector');
    }

    /** Spec §18 — an investor can select multiple stages. */
    public function stages(): BelongsToMany
    {
        // Explicit pivot name — Eloquent's default convention would be
        // investor_profile_stage, but the migration named it investor_stage.
        return $this->belongsToMany(Stage::class, 'investor_stage');
    }

    public function matches(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(MatchModel::class);
    }

    /**
     * Convenience accessor — the actual matching engine (Phase 5) will
     * work in minor units directly, this is purely for display.
     */
    public function ticketRangeLabel(): ?string
    {
        if (! $this->ticket_min_minor && ! $this->ticket_max_minor) {
            return null;
        }

        $fmt = fn (?int $minor) => $minor === null ? '?' : number_format($minor / 100, 0, ',', ' ');

        return "{$fmt($this->ticket_min_minor)} – {$fmt($this->ticket_max_minor)} {$this->ticket_currency}";
    }
}
