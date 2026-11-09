<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancingOfferComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'financing_offer_id',
        'financial_instrument_id',
        'offered_amount_minor',
    ];

    protected function casts(): array
    {
        return [
            'offered_amount_minor' => 'integer',
        ];
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(FinancingOffer::class, 'financing_offer_id');
    }

    public function instrument(): BelongsTo
    {
        return $this->belongsTo(FinancialInstrument::class, 'financial_instrument_id');
    }
}
