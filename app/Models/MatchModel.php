<?php

namespace App\Models;

use App\Enums\MatchStatus;
use Database\Factories\MatchModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model for the "matches" table (spec §22/§84). The class is named
 * `MatchModel` — not `Match` — because `match` is a reserved keyword
 * in PHP 8 and a class named `Match` cannot parse.
 */
class MatchModel extends Model
{
    /** @use HasFactory<MatchModelFactory> */
    use HasFactory;

    protected $table = 'matches';

    protected $fillable = ['score', 'breakdown', 'status', 'computed_at'];

    // project_id / investor_profile_id are set only via the relationship
    // (Project::matches()->create() / InvestorProfile::matches()->create())
    // inside MatchScoringService — never from request input, since a
    // Match is a computed artifact, not something a user submits.

    protected function casts(): array
    {
        return [
            'breakdown' => 'array',
            'status' => MatchStatus::class,
            'computed_at' => 'datetime',
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
}
