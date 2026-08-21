<?php

namespace App\Jobs;

use App\Enums\MatchStatus;
use App\Events\MatchCreated;
use App\Models\InvestorProfile;
use App\Models\MatchModel;
use App\Models\Project;
use App\Services\Matching\MatchScoringService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Queued (spec §115 — AI analysis is exactly the kind of heavy task that
 * shouldn't block a request). Idempotent by design: re-dispatching this
 * for the same project just updateOrCreate()s the same Match rows rather
 * than duplicating them (spec §41's idempotency principle, applied here
 * even though nothing financial is happening).
 */
class ComputeMatchesForProject implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Project $project) {}

    public function handle(MatchScoringService $scoring): void
    {
        InvestorProfile::query()
            ->where('onboarding_completed', true)
            ->chunkById(50, function ($investors) use ($scoring) {
                foreach ($investors as $investor) {
                    $result = $scoring->score($this->project, $investor);

                    // project_id/investor_profile_id are deliberately
                    // outside MatchModel::$fillable — updateOrCreate()'s
                    // create path would silently drop them, so the create
                    // branch is written out manually with forceCreate().
                    $attributes = [
                        'score' => $result['score'],
                        'breakdown' => $result['breakdown'],
                        'computed_at' => now(),
                    ];
                    $match = MatchModel::where('project_id', $this->project->id)
                        ->where('investor_profile_id', $investor->id)
                        ->first();

                    if ($match) {
                        $match->fill($attributes)->save();
                    } else {
                        $match = MatchModel::forceCreate(array_merge(
                            ['project_id' => $this->project->id, 'investor_profile_id' => $investor->id],
                            $attributes,
                        ));
                    }

                    if ($match->wasRecentlyCreated) {
                        $match->status = MatchStatus::Pending;
                        $match->save();
                        MatchCreated::dispatch($match);
                    }
                }
            });
    }
}
