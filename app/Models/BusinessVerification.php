<?php

namespace App\Models;

use App\Enums\LegalForm;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusinessVerification extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'compliance_case_id',
        'company_name',
        'legal_form',
        'rccm_number',
        'tax_identification_number',
        'national_identification_number',
        'registered_office_address',
        'country_of_incorporation',
        'date_of_incorporation',
        'corporate_purpose',
        'share_capital_minor',
        'share_capital_currency',
        'main_economic_activity',
        'industry_sector',
        'legal_representative_user_id',
        'legal_representative_name',
        'legal_representative_title',
        'has_sectorial_authorization',
    ];

    protected function casts(): array
    {
        return [
            'legal_form' => LegalForm::class,
            'share_capital_minor' => 'integer',
            'date_of_incorporation' => 'date',
            'has_sectorial_authorization' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function complianceCase(): BelongsTo
    {
        return $this->belongsTo(ComplianceCase::class);
    }

    public function legalRepresentative(): BelongsTo
    {
        return $this->belongsTo(User::class, 'legal_representative_user_id');
    }

    public function legalDocuments(): HasMany
    {
        return $this->hasMany(BusinessLegalDocument::class);
    }

    public function beneficialOwners(): HasMany
    {
        return $this->hasMany(BeneficialOwner::class);
    }

    public function ownershipRelationships(): HasMany
    {
        return $this->hasMany(OwnershipRelationship::class);
    }

    public function isVerified(): bool
    {
        return $this->status === 'verified';
    }
}
