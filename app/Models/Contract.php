<?php

namespace App\Models;

use App\Enums\ContractStatus;
use App\Enums\FundingType;
use Database\Factories\ContractFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Contract extends Model
{
    /** @use HasFactory<ContractFactory> */
    use HasFactory;

    protected $fillable = []; // every field is set exclusively via Actions — see app/Actions/Contracts/*

    protected function casts(): array
    {
        return [
            'status' => ContractStatus::class,
            'investment_type' => FundingType::class,
            'terms' => 'array',
            'amount_minor' => 'integer',
            'entrepreneur_accepted_at' => 'datetime',
            'investor_accepted_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<DealRoom, $this> */
    public function dealRoom(): BelongsTo
    {
        return $this->belongsTo(DealRoom::class);
    }

    /** @return BelongsTo<User, $this> */
    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by_user_id');
    }

    /** @return HasMany<ContractVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(ContractVersion::class)->orderByDesc('version');
    }

    /** @return HasOne<Escrow, $this> */
    public function escrow(): HasOne
    {
        return $this->hasOne(Escrow::class);
    }

    public function latestVersion(): ?ContractVersion
    {
        return $this->versions()->first();
    }

    public function isFullyAccepted(): bool
    {
        return $this->entrepreneur_accepted_at !== null && $this->investor_accepted_at !== null;
    }

    public function hasAccepted(User $user): bool
    {
        return ($user->id === $this->dealRoom->entrepreneur_user_id && $this->entrepreneur_accepted_at !== null)
            || ($user->id === $this->dealRoom->investor_user_id && $this->investor_accepted_at !== null);
    }
}
