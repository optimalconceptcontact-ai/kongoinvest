<?php

namespace App\Models;

use App\Enums\PaymentProviderName;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    protected $fillable = ['provider_reference', 'status', 'failure_reason', 'metadata'];

    // payer_id, purpose, payable, provider, amount_minor, currency, and
    // idempotency_key are set only at creation via CreatePayment — never
    // updated afterward. Only status/reference/failure_reason change,
    // and only through ProcessWebhookEvent.

    protected function casts(): array
    {
        return [
            'purpose' => PaymentPurpose::class,
            'provider' => PaymentProviderName::class,
            'status' => PaymentStatus::class,
            'amount_minor' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payer_id');
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function isFinal(): bool
    {
        return in_array($this->status, [
            PaymentStatus::Succeeded, PaymentStatus::Failed,
            PaymentStatus::Refunded, PaymentStatus::Cancelled,
        ], true);
    }
}
