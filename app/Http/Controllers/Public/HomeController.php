<?php

namespace App\Http\Controllers\Public;

use App\Data\PartnerData;
use App\Data\PlatformStatsData;
use App\Data\ProjectData;
use App\Data\SubscriptionPlanData;
use App\Data\TestimonialData;
use App\Http\Controllers\Controller;
use App\Models\InvestorProfile;
use App\Models\Partner;
use App\Models\Project;
use App\Models\Sector;
use App\Models\SubscriptionPlan;
use App\Models\Testimonial;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(Request $request, SubscriptionService $subscriptions): Response
    {
        $viewer = $request->user();
        $currentId = $viewer ? $subscriptions->currentPlanFor($viewer)->id : null;

        return Inertia::render('Public/Welcome', [
            // Every number here is a live COUNT query — spec §64:
            // "Ne jamais inventer de chiffres réels." If the platform is
            // young and these are small/zero, that's what the page shows.
            'stats' => Inertia::defer(fn () => new PlatformStatsData(
                publishedProjects: Project::published()->count(),
                entrepreneurs: User::role('entrepreneur')->count(),
                investors: User::role('investor')->count(),
                sectors: Sector::where('is_active', true)->count(),
                investorCountries: InvestorProfile::whereNotNull('country_code')->distinct('country_code')->count('country_code'),
            )),
            'featuredProjects' => Inertia::defer(fn () => Project::published()
                ->with(['sectors', 'stages', 'user'])
                ->latest('published_at')
                ->take(3)
                ->get()
                ->map(fn (Project $p) => ProjectData::fromModel($p))),
            // Both of these are genuinely empty until an admin (Phase 7)
            // publishes real ones — the React page hides each section
            // entirely when the array is empty rather than padding it
            // with placeholder content (spec §65, §69).
            'partners' => Partner::published()->get()->map(fn (Partner $p) => PartnerData::fromModel($p)),
            'testimonials' => Testimonial::published()->get()->map(fn (Testimonial $t) => TestimonialData::fromModel($t)),
            // Same role-aware plans as the Pricing page (spec §29-32) for
            // the homepage pricing section — guests see all four.
            'plans' => Inertia::defer(fn () => SubscriptionPlan::where('is_active', true)
                ->orderBy('order')
                ->get()
                ->map(fn (SubscriptionPlan $plan) => SubscriptionPlanData::fromModel($plan, $viewer, isCurrent: $currentId !== null && $plan->id === $currentId))),
        ]);
    }
}
