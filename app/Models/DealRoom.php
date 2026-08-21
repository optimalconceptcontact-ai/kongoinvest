<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DealRoom extends Model
{
    /** @use HasFactory<\Database\Factories\DealRoomFactory> */
    use HasFactory;

    protected $fillable = []; // created only by RespondToConnection::accept() — never from request input

    public function connection(): BelongsTo
    {
        return $this->belongsTo(Connection::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function entrepreneur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entrepreneur_user_id');
    }

    public function investor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'investor_user_id');
    }

    public function conversation(): HasOne
    {
        return $this->hasOne(Conversation::class);
    }

    public function documents(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function contracts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function escrow(): \Illuminate\Database\Eloquent\Relations\HasOneThrough
    {
        return $this->hasOneThrough(Escrow::class, Contract::class);
    }

    public function isParticipant(User $user): bool
    {
        return $user->id === $this->entrepreneur_user_id || $user->id === $this->investor_user_id;
    }
}
