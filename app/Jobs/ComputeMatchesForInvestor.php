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

class ComputeMatchesForInvestor implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public InvestorProfile $investor) {}

    public function handle(MatchScoringService $scoring): void
    {
        Project::query()
            ->published()
            ->chunkById(50, function ($projects) use ($scoring) {
                foreach ($projects as $project) {
                    $result = $scoring->score($project, $this->investor);

                    // project_id/investor_profile_id are deliberately
                    // outside MatchModel::$fillable — updateOrCreate()'s
                    // create path would silently drop them, so the create
                    // branch is written out manually with forceCreate().
                    $attributes = [
                        'score' => $result['score'],
                        'breakdown' => $result['breakdown'],
                        'computed_at' => now(),
                    ];
                    $match = MatchModel::where('project_id', $project->id)
                        ->where('investor_profile_id', $this->investor->id)
                        ->first();

                    if ($match) {
                        $match->fill($attributes)->save();
                    } else {
                        $match = MatchModel::forceCreate(array_merge(
                            ['project_id' => $project->id, 'investor_profile_id' => $this->investor->id],
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
