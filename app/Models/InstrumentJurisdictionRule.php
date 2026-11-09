<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstrumentJurisdictionRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'financial_instrument_id',
        'jurisdiction_code',
        'legal_status',
        'requires_incorporation',
        'minimum_capital_minor',
        'regulatory_notes',
        'compliance_requirements',
    ];

    protected function casts(): array
    {
        return [
            'requires_incorporation' => 'boolean',
            'minimum_capital_minor' => 'integer',
            'compliance_requirements' => 'array',
        ];
    }

    public function instrument(): BelongsTo
    {
        return $this->belongsTo(FinancialInstrument::class, 'financial_instrument_id');
    }
}
