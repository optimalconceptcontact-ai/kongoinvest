<?php

namespace App\Models;

use App\Enums\UboControlNature;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OwnershipRelationship extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_verification_id',
        'parent_entity_type',
        'parent_name',
        'parent_registration_number',
        'parent_country',
        'child_entity_name',
        'child_registration_number',
        'tier_level',
        'equity_percentage_bps',
        'voting_percentage_bps',
        'control_mechanism',
        'is_ultimate_owner',
    ];

    protected function casts(): array
    {
        return [
            'tier_level' => 'integer',
            'equity_percentage_bps' => 'integer',
            'voting_percentage_bps' => 'integer',
            'control_mechanism' => UboControlNature::class,
            'is_ultimate_owner' => 'boolean',
        ];
    }

    public function businessVerification(): BelongsTo
    {
        return $this->belongsTo(BusinessVerification::class);
    }

    public function equityPercentage(): float
    {
        return round($this->equity_percentage_bps / 100, 2);
    }
}
