<?php

namespace App\Http\Controllers\Public;

use App\Data\AiAnalysisData;
use App\Data\AiConversationData;
use App\Enums\AiInteractionType;
use App\Enums\SubscriptionFeature;
use App\Models\AiConversation;
use App\Services\SubscriptionService;
use App\Data\ProjectData;
use App\Data\SectorData;
use App\Data\StageData;
use App\Enums\ConnectionStatus;
use App\Http\Controllers\Controller;
use App\Models\Connection;
use App\Models\Project;
use App\Models\Sector;
use App\Models\Stage;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Project::query()
            ->published()
            ->visibleTo($request->user())
            ->with(['sectors', 'stages', 'user']);

        if ($keyword = $request->string('q')->trim()->value()) {
            $query->where(fn ($q) => $q
                ->where('title', 'like', "%{$keyword}%")
                ->orWhere('description', 'like', "%{$keyword}%"));
        }

        if ($sectorId = $request->integer('sector')) {
            $query->whereHas('sectors', fn ($q) => $q->where('sectors.id', $sectorId));
        }

        if ($stageId = $request->integer('stage')) {
            $query->whereHas('stages', fn ($q) => $q->where('stages.id', $stageId));
        }

        if ($province = $request->string('province')->trim()->value()) {
            $query->whereJsonContains('provinces', $province);
        }

        // Never load thousands of rows into a page (spec §94) — always
        // server-side pagination.
        $projects = $query->latest('published_at')->paginate(12)->withQueryString();

        return Inertia::render('Public/Projects', [
            'projects' => $projects->through(fn (Project $p) => ProjectData::fromModel($p)),
            'sectors' => Sector::where('is_active', true)->orderBy('order')->get()->map(fn ($s) => SectorData::fromModel($s)),
            'stages' => Stage::where('is_active', true)->orderBy('order')->get()->map(fn ($s) => StageData::fromModel($s)),
            'filters' => $request->only(['q', 'sector', 'stage', 'province']),
        ]);
    }

    public function show(Request $request, Project $project, SubscriptionService $subscriptions): Response
    {
        // Route-model binding resolves by slug (see routes/web.php). The
        // Policy — not the query — is what actually decides visibility;
        // a direct URL guess at a private project's slug still 403s.
        $this->authorize('view', $project);

        $connection = null;
        $user = $request->user();

        if ($user?->isInvestor() && $user->investorProfile) {
            $connection = Connection::where('project_id', $project->id)
                ->where('investor_profile_id', $user->investorProfile->id)
                ->whereIn('status', [ConnectionStatus::Pending->value, ConnectionStatus::Accepted->value])
                ->with('dealRoom')
                ->latest()
                ->first();
        }

        // Only the most recent analysis is shown — spec §50-52 present
        // this as the current AI assessment, not a running history the
        // page needs to page through.
        $latestAnalysis = $project->aiAnalyses()->first();

        return Inertia::render('Public/ProjectDetail', [
            'project' => ProjectData::fromModel($project->load(['sectors', 'stages', 'user', 'media']), full: true),
            'imageUrls' => $project->getMedia('project-images')->map(fn ($m) => $m->getUrl())->values(),
            'connection' => $connection ? [
                'id' => $connection->id,
                'status' => $connection->status->value,
                'dealRoomId' => $connection->dealRoom?->id,
            ] : null,
            'aiAnalysis' => $latestAnalysis ? AiAnalysisData::fromModel($latestAnalysis) : null,
            'hasAdvisorFeature' => $user?->isInvestor() && $subscriptions->hasFeature($user, SubscriptionFeature::AiInvestmentAdvisor),
            'advisorConversation' => $this->advisorConversationFor($user, $project),
            'isSaved' => $user?->isInvestor() && $project->isSavedBy($user),
        ]);
    }

    private function advisorConversationFor(?\App\Models\User $user, Project $project): ?AiConversationData
    {
        if (! $user?->isInvestor()) {
            return null;
        }

        $conversation = AiConversation::where('user_id', $user->id)
            ->where('project_id', $project->id)
            ->where('type', AiInteractionType::InvestorAdvisor->value)
            ->with('messages')
            ->first();

        return $conversation ? AiConversationData::fromModel($conversation) : null;
    }
}
