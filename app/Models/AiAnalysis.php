<?php

namespace App\Models;

use App\Enums\AiRequestStatus;
use Database\Factories\AiAnalysisFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAnalysis extends Model
{
    /** @use HasFactory<AiAnalysisFactory> */
    use HasFactory;

    // Every field here is written exclusively by ProjectAnalysisService —
    // never from raw request input. Listed explicitly (not empty) so
    // that trusted service, and only it, can mass-assign in one create()
    // call rather than fighting Eloquent guard rules field-by-field.
    protected $fillable = [
        'project_id', 'requested_by_user_id', 'status', 'provider', 'content', 'investment_score', 'failure_reason', 'tokens_used',
    ];

    protected function casts(): array
    {
        return [
            'status' => AiRequestStatus::class,
            // sub-scores, disclaimer, and every §51 narrative section live
            // inside `content` as one structured blob (ProjectAnalysisData's
            // shape) — investment_score is pulled out as its own column
            // purely so it can be queried/sorted without touching JSON.
            'content' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }
}
