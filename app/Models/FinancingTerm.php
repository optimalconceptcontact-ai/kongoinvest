<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FinancingTerm extends Model
{
    use HasFactory;

    protected $fillable = [
        'termable_type',
        'termable_id',
        'valuation_pre_money_minor',
        'valuation_post_money_minor',
        'interest_rate_bps',
        'duration_months',
        'grace_period_months',
        'revenue_share_percentage',
        'cap_minor',
        'discount_rate_bps',
        'collateral_required',
        'collateral_description',
        'covenants',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'valuation_pre_money_minor' => 'integer',
            'valuation_post_money_minor' => 'integer',
            'interest_rate_bps' => 'integer',
            'duration_months' => 'integer',
            'grace_period_months' => 'integer',
            'revenue_share_percentage' => 'decimal:2',
            'cap_minor' => 'integer',
            'discount_rate_bps' => 'integer',
            'collateral_required' => 'boolean',
            'covenants' => 'array',
            'metadata' => 'array',
        ];
    }

    public function termable(): MorphTo
    {
        return $this->morphTo();
    }
}
