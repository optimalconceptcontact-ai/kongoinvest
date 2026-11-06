<?php

namespace App\Models;

use Database\Factories\DealRoomFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class DealRoom extends Model
{
    /** @use HasFactory<DealRoomFactory> */
    use HasFactory;

    protected $fillable = []; // created only by RespondToConnection::accept() — never from request input

    /** @return BelongsTo<Connection, $this> */
    public function connection(): BelongsTo
    {
        return $this->belongsTo(Connection::class);
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsTo<User, $this> */
    public function entrepreneur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entrepreneur_user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function investor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'investor_user_id');
    }

    /** @return HasOne<Conversation, $this> */
    public function conversation(): HasOne
    {
        return $this->hasOne(Conversation::class);
    }

    /** @return HasMany<Document, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /** @return HasMany<Contract, $this> */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    /** @return HasOneThrough<Escrow, Contract, $this> */
    public function escrow(): HasOneThrough
    {
        return $this->hasOneThrough(Escrow::class, Contract::class);
    }

    /** @return HasOne<InvestmentContractWorkspace, $this> */
    public function contractWorkspace(): HasOne
    {
        return $this->hasOne(InvestmentContractWorkspace::class);
    }

    public function isParticipant(User $user): bool
    {
        return $user->id === $this->entrepreneur_user_id || $user->id === $this->investor_user_id;
    }
}
