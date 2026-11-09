<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancingRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'company_id',
        'title',
        'total_amount_minor',
        'currency',
        'min_acceptable_minor',
        'equity_dilution_percentage_target',
        'preferred_channel_id',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'total_amount_minor' => 'integer',
            'min_acceptable_minor' => 'integer',
            'equity_dilution_percentage_target' => 'decimal:2',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function preferredChannel(): BelongsTo
    {
        return $this->belongsTo(FinancingChannel::class, 'preferred_channel_id');
    }

    public function components(): HasMany
    {
        return $this->hasMany(FinancingRequestComponent::class)->orderBy('sort_order');
    }

    public function offers(): HasMany
    {
        return $this->hasMany(FinancingOffer::class);
    }
}
