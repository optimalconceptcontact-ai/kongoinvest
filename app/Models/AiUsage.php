<?php

namespace App\Models;

use App\Enums\AiInteractionType;
use App\Enums\AiRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiUsage extends Model
{
    // Eloquent's default convention would be ai_usages, but the migration
    // named the table ai_usage (singular).
    protected $table = 'ai_usage';

    public $timestamps = false; // only created_at, set explicitly — append-only usage ledger

    protected $fillable = ['user_id', 'project_id', 'type', 'provider', 'tokens_used', 'status', 'created_at'];

    protected function casts(): array
    {
        return [
            'type' => AiInteractionType::class,
            'status' => AiRequestStatus::class,
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
