<?php

namespace App\Models;

use App\Enums\UboControlNature;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BeneficialOwner extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_verification_id',
        'user_id',
        'first_name',
        'last_name',
        'postnom',
        'date_of_birth',
        'nationality',
        'country_of_residence',
        'residential_address',
        'is_pep',
        'pep_details',
        'direct_ownership_bps',
        'indirect_ownership_bps',
        'voting_rights_bps',
        'control_nature',
        'meets_statutory_threshold',
        'justification_notes',
        'identity_document_file_path',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'is_pep' => 'boolean',
            'direct_ownership_bps' => 'integer',
            'indirect_ownership_bps' => 'integer',
            'voting_rights_bps' => 'integer',
            'control_nature' => UboControlNature::class,
            'meets_statutory_threshold' => 'boolean',
            'is_verified' => 'boolean',
        ];
    }

    public function businessVerification(): BelongsTo
    {
        return $this->belongsTo(BusinessVerification::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function totalEffectiveOwnershipBps(): int
    {
        return $this->direct_ownership_bps + $this->indirect_ownership_bps;
    }

    public function totalEffectivePercentage(): float
    {
        return round($this->totalEffectiveOwnershipBps() / 100, 2);
    }

    public function fullName(): string
    {
        $parts = array_filter([$this->first_name, $this->last_name, $this->postnom]);

        return implode(' ', $parts);
    }
}
