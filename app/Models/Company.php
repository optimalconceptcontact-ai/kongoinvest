<?php

namespace App\Models;

use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory, HasSlug;

    protected $fillable = [
        'name', 'registration_number', 'legal_form', 'description',
        'website', 'country_code', 'city',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    /**
     * Instantanés juridiques successifs — le libellé SARLU/SASU se
     * lit sur la fiche courante.
     *
     * @return HasMany<CompanyLegalRecord, $this>
     */
    public function legalRecords(): HasMany
    {
        return $this->hasMany(CompanyLegalRecord::class);
    }

    public function businessVerifications(): HasMany
    {
        return $this->hasMany(BusinessVerification::class)->latest();
    }

    public function latestVerification(): HasOne
    {
        return $this->hasOne(BusinessVerification::class)->latestOfMany();
    }

    /**
     * Pre-incorporation readiness records converted into this company.
     *
     * @return HasMany<ProjectFormationReadiness, $this>
     */
    public function formationReadiness(): HasMany
    {
        return $this->hasMany(ProjectFormationReadiness::class, 'converted_to_company_id');
    }
}
