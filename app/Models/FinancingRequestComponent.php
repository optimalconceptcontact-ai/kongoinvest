<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class FinancingRequestComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'financing_request_id',
        'financial_instrument_id',
        'target_amount_minor',
        'min_amount_minor',
        'is_mandatory',
        'sort_order',
        'status',
        'terms_summary',
    ];

    protected function casts(): array
    {
        return [
            'target_amount_minor' => 'integer',
            'min_amount_minor' => 'integer',
            'is_mandatory' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(FinancingRequest::class, 'financing_request_id');
    }

    public function instrument(): BelongsTo
    {
        return $this->belongsTo(FinancialInstrument::class, 'financial_instrument_id');
    }

    public function terms(): MorphOne
    {
        return $this->morphOne(FinancingTerm::class, 'termable');
    }
}
