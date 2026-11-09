<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectFormationReadiness extends Model
{
    use HasFactory;

    protected $table = 'project_formation_readiness';

    protected $fillable = [
        'project_id',
        'intended_legal_form',
        'intended_company_name',
        'intended_corporate_purpose',
        'intended_registered_office',
        'intended_capital_minor',
        'intended_capital_currency',
        'indicative_cofounders',
        'recommended_legal_form',
        'recommendation_rationale',
        'readiness_score',
        'readiness_checklist',
        'converted_to_company_id',
        'converted_at',
    ];

    protected function casts(): array
    {
        return [
            'intended_capital_minor' => 'integer',
            'indicative_cofounders' => 'array',
            'readiness_score' => 'integer',
            'readiness_checklist' => 'array',
            'converted_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function convertedCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'converted_to_company_id');
    }

    public function isConverted(): bool
    {
        return $this->converted_to_company_id !== null;
    }
}
