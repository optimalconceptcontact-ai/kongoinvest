<?php

namespace App\Models;

use App\Enums\EscrowStatus;
use Database\Factories\EscrowFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Escrow extends Model
{
    /** @use HasFactory<EscrowFactory> */
    use HasFactory;

    protected $fillable = []; // every field set exclusively via app/Actions/Escrow/* — never from request input

    protected function casts(): array
    {
        return [
            'status' => EscrowStatus::class,
            'amount_minor' => 'integer',
            'funded_at' => 'datetime',
            'released_at' => 'datetime',
            'refunded_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function dealRoom(): BelongsTo
    {
        return $this->belongsTo(DealRoom::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function investor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'investor_user_id');
    }

    public function entrepreneur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entrepreneur_user_id');
    }

    /** The Payment that funded this Escrow — set once funding succeeds. */
    public function fundingPayment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    /** The reverse — Payment::payable() when purpose=investment points back here. */
    public function payment(): MorphOne
    {
        return $this->morphOne(Payment::class, 'payable');
    }

    public function events(): HasMany
    {
        return $this->hasMany(EscrowEvent::class)->orderBy('created_at');
    }

    public function disputes(): HasMany
    {
        return $this->hasMany(Dispute::class);
    }

    public function openDispute(): ?Dispute
    {
        return $this->disputes()->whereIn('status', ['opened', 'under_review'])->latest()->first();
    }

    public function isParticipant(User $user): bool
    {
        return $user->id === $this->investor_user_id || $user->id === $this->entrepreneur_user_id;
    }
}
