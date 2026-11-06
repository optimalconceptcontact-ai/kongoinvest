<?php

namespace App\Http\Controllers\Public;

use App\Data\AiAnalysisData;
use App\Data\AiConversationData;
use App\Data\ProjectData;
use App\Data\SectorData;
use App\Data\StageData;
use App\Enums\AiInteractionType;
use App\Enums\ConnectionStatus;
use App\Enums\SubscriptionFeature;
use App\Http\Controllers\Controller;
use App\Models\AiAnalysis;
use App\Models\AiConversation;
use App\Models\Connection;
use App\Models\Project;
use App\Models\Sector;
use App\Models\Stage;
use App\Models\User;
use App\Services\SubscriptionService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ProjectController extends Controller
{
    public function index(Request $request): Response
    {
        if ($request->user()?->isEntrepreneur()) {
            abort(403, 'Les entrepreneurs ne peuvent consulter que leurs propres projets.');
        }

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
        // server-side pagination. The query itself runs lazily through
        // `Inertia::defer` so the page shell (filters, header) paints
        // before the project grid resolves.
        return Inertia::render('Public/Projects', [
            'projects' => Inertia::defer(fn () => $query
                ->latest('published_at')
                ->paginate(12)
                ->withQueryString()
                ->through(fn (Project $p) => ProjectData::fromModel($p))),
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
        // page needs to page through. AI + media are deferred so the page
        // skeleton (title, sections) paints immediately.
        return Inertia::render('Public/ProjectDetail', [
            'project' => ProjectData::fromModel($project->load(['sectors', 'stages', 'user', 'media']), full: true),
            'imageUrls' => Inertia::defer(fn () => $project->getMedia('project-images')->map(fn ($m) => $m->getUrl())->values()),
            'connection' => $connection ? [
                'id' => $connection->id,
                'status' => $connection->status->value,
                'dealRoomId' => $connection->dealRoom?->id,
            ] : null,
            'aiAnalysis' => Inertia::defer(fn () => ($latestAnalysis = AiAnalysis::query()->where('project_id', $project->id)->latest()->first()) ? AiAnalysisData::fromModel($latestAnalysis) : null),
            'hasAdvisorFeature' => $user?->isInvestor() && $subscriptions->hasFeature($user, SubscriptionFeature::AiInvestmentAdvisor),
            'advisorConversation' => Inertia::defer(fn () => $this->advisorConversationFor($user, $project)),
            'isSaved' => $user?->isInvestor() && $project->isSavedBy($user),
        ]);
    }

    public function report(Request $request, Project $project): SymfonyResponse
    {
        $this->authorize('view', $project);

        $latestAnalysis = AiAnalysis::query()->where('project_id', $project->id)->latest()->first();

        $html = $this->projectReportHtml($project, $latestAnalysis);

        if (! defined('DOMPDF_ENABLE_AUTOLOAD')) {
            define('DOMPDF_ENABLE_AUTOLOAD', false);
        }

        $dompdfOptions = new Options;
        $dompdfOptions->setIsRemoteEnabled(false);
        $dompdf = new Dompdf($dompdfOptions);
        $dompdf->loadHtml($html);
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename='.$project->slug.'.pdf',
        ]);
    }

    private function advisorConversationFor(?User $user, Project $project): ?AiConversationData
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

    private function projectReportHtml(Project $project, ?AiAnalysis $latestAnalysis): string
    {
        $sections = [
            'Résumé exécutif' => $project->executive_summary,
            'Problème' => $project->problem,
            'Solution' => $project->solution,
            'Modèle économique' => $project->business_model,
            'Marché' => $project->market,
            'Avantage concurrentiel' => $project->competitive_advantage,
            'Traction' => $project->traction,
            'Informations financières' => $project->financial_information,
            'Impact' => $project->impact,
            'Risques' => $project->risks,
        ];

        $html = '<!DOCTYPE html><html lang="fr"><head><meta charset="utf-8"><style>'
            .'body{font-family:DejaVu Sans,sans-serif;color:#1a2332;padding:24px;}'
            .'h1{font-size:22px;margin:0 0 4px;}'
            .'h2{font-size:13px;margin:24px 0 6px;text-transform:uppercase;letter-spacing:1px;color:#4a5568;}'
            .'p{font-size:11px;line-height:1.6;margin:0 0 6px;}'
            .'</style></head><body>'
            .'<h1>'.e($project->title).'</h1>'
            .'<div style="font-size:10px;color:#718096;margin-bottom:8px;">'
            .'Par '.e($project->user->name).' — '
            .e(implode(', ', $project->provinces)).' — '
            .e(implode(', ', $project->cities))
            .'</div>'
            .'<h2>Présentation</h2><p>'.e($project->description).'</p>';

        foreach ($sections as $title => $body) {
            if (filled($body)) {
                $html .= '<h2>'.e($title).'</h2><p>'.e((string) $body).'</p>';
            }
        }

        if ($latestAnalysis) {
            $analysis = AiAnalysisData::fromModel($latestAnalysis);

            $html .= '<h2>Analyse IA</h2>';
            if (filled($analysis->investmentScore)) {
                $html .= '<p>Score d\'investissement : '.$analysis->investmentScore.'/10</p>';
            }
            $analysisSections = [
                'Exécutif' => $analysis->executiveSummary,
                'Modèle économique' => $analysis->businessModelAssessment,
                'Potentiel du marché' => $analysis->marketPotential,
                'Adéquation problème/solution' => $analysis->problemSolutionFit,
                'Avantage concurrentiel' => $analysis->competitiveAdvantage,
                'Évaluation de l\'équipe' => $analysis->teamAssessment,
                'Traction' => $analysis->tractionAssessment,
                'Potentiel financier' => $analysis->financialPotential,
                'Scalabilité' => $analysis->scalability,
            ];

            foreach ($analysisSections as $title => $body) {
                if (filled($body)) {
                    $html .= '<h2>'.e($title).'</h2><p>'.e((string) $body).'</p>';
                }
            }

            $analysisLists = [
                'strengths' => $analysis->strengths,
                'weaknesses' => $analysis->weaknesses,
                'risks' => $analysis->risks,
                'redFlags' => $analysis->redFlags,
                'dueDiligenceQuestions' => $analysis->dueDiligenceQuestions,
            ];

            foreach ($analysisLists as $field => $items) {
                $title = ucfirst(str_replace(['_', '-'], ' ', $field));
                foreach ($items as $item) {
                    $html .= '<h2>'.e($title).'</h2><p>'.e((string) $item).'</p>';
                }
            }

            if (filled($analysis->fundingAssessment)) {
                $html .= '<h2>Évaluation du financement</h2><p>'.e((string) $analysis->fundingAssessment).'</p>';
            }
            if (filled($analysis->recommendation)) {
                $html .= '<h2>Recommandation</h2><p>'.e((string) $analysis->recommendation).'</p>';
            }
            if (filled($analysis->disclaimer)) {
                $html .= '<h2>Note</h2><p>'.e((string) $analysis->disclaimer).'</p>';
            }
        }

        return $html.'</body></html>';
    }
}
