<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancialInstrument extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'code',
        'description',
        'instrument_category',
        'repayment_required',
        'ownership_dilution',
        'remuneration_type',
        'conversion_possible',
        'collateral_possible',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'repayment_required' => 'boolean',
            'ownership_dilution' => 'boolean',
            'conversion_possible' => 'boolean',
            'collateral_possible' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function financingTypes(): BelongsToMany
    {
        return $this->belongsToMany(FinancingType::class, 'financing_type_instrument')
            ->withPivot('is_primary', 'sort_order')
            ->withTimestamps();
    }

    public function jurisdictionRules(): HasMany
    {
        return $this->hasMany(InstrumentJurisdictionRule::class);
    }

    public function stages(): BelongsToMany
    {
        return $this->belongsToMany(Stage::class, 'instrument_stage_compatibilities')
            ->withPivot('is_recommended')
            ->withTimestamps();
    }

    public function investorProfiles(): BelongsToMany
    {
        return $this->belongsToMany(InvestorProfile::class, 'investor_profile_instruments')
            ->withPivot('ticket_min_minor', 'ticket_max_minor')
            ->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
