<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class FinancingOffer extends Model
{
    use HasFactory;

    protected $fillable = [
        'investor_profile_id',
        'financing_request_id',
        'project_id',
        'total_offered_minor',
        'currency',
        'offer_type',
        'status',
        'valid_until',
    ];

    protected function casts(): array
    {
        return [
            'total_offered_minor' => 'integer',
            'valid_until' => 'date',
        ];
    }

    public function investorProfile(): BelongsTo
    {
        return $this->belongsTo(InvestorProfile::class);
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(FinancingRequest::class, 'financing_request_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function components(): HasMany
    {
        return $this->hasMany(FinancingOfferComponent::class);
    }

    public function terms(): MorphOne
    {
        return $this->morphOne(FinancingTerm::class, 'termable');
    }
}
