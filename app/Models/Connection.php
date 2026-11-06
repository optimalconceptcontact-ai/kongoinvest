<?php

namespace App\Models;

use App\Enums\ConnectionStatus;
use Database\Factories\ConnectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Connection extends Model
{
    /** @use HasFactory<ConnectionFactory> */
    use HasFactory;

    protected $fillable = ['message'];

    // project_id, investor_profile_id, requested_by_user_id, status, and
    // responded_at are all set exclusively by RequestConnection /
    // RespondToConnection / CancelConnection — never from raw request
    // input (spec §100).

    protected function casts(): array
    {
        return [
            'status' => ConnectionStatus::class,
            'responded_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function investorProfile(): BelongsTo
    {
        return $this->belongsTo(InvestorProfile::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function dealRoom(): HasOne
    {
        return $this->hasOne(DealRoom::class);
    }
}
