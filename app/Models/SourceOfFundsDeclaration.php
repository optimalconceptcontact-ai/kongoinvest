<?php

namespace App\Models;

use App\Enums\SourceOfFundsCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SourceOfFundsDeclaration extends Model
{
    use HasFactory;

    protected $fillable = [
        'compliance_case_id',
        'user_id',
        'operation_reference',
        'amount_minor',
        'currency',
        'funds_category',
        'origin_description',
        'bank_name',
        'bank_country',
        'account_iban_or_number_encrypted',
        'supporting_document_paths',
    ];

    protected function casts(): array
    {
        return [
            'amount_minor' => 'integer',
            'funds_category' => SourceOfFundsCategory::class,
            'account_iban_or_number_encrypted' => 'encrypted',
            'supporting_document_paths' => 'array',
            'is_verified' => 'boolean',
        ];
    }

    public function complianceCase(): BelongsTo
    {
        return $this->belongsTo(ComplianceCase::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function amountFormatted(): string
    {
        $amount = number_format($this->amount_minor / 100, 2, ',', ' ');

        return "{$amount} {$this->currency}";
    }
}
