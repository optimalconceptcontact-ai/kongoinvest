<?php

namespace App\Models;

use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
}
