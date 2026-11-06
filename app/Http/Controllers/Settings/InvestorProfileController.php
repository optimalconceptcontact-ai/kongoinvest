<?php

namespace App\Http\Controllers\Settings;

use App\Data\InvestorProfileData;
use App\Data\SectorData;
use App\Data\StageData;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateInvestorProfileRequest;
use App\Jobs\ComputeMatchesForInvestor;
use App\Models\Company;
use App\Models\Sector;
use App\Models\Stage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class InvestorProfileController extends Controller
{
    /**
     * Plain Request, not UpdateInvestorProfileRequest — that FormRequest's
     * rules() has several `required` fields (country_code, investor_type,
     * ticket_currency) meant for the PUT below. Type-hinting it here too
     * made Laravel validate this GET's empty body against those rules on
     * every load, fail, and redirect back to wherever the link was
     * clicked from (the dashboard) instead of ever rendering the page.
     * The `role:investor` route middleware already gates access here.
     */
    public function edit(Request $request): Response
    {
        $profile = $request->user()->investorProfile()->with(['company', 'sectors', 'stages'])->firstOrFail();

        return Inertia::render('Settings/InvestorProfile', [
            'profile' => InvestorProfileData::fromModel($profile),
            'sectors' => Sector::where('is_active', true)->orderBy('order')->get()->map(fn ($s) => SectorData::fromModel($s)),
            'stages' => Stage::where('is_active', true)->orderBy('order')->get()->map(fn ($s) => StageData::fromModel($s)),
        ]);
    }

    public function update(UpdateInvestorProfileRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $profile = DB::transaction(function () use ($request, $data) {
            $profile = $request->user()->investorProfile()->firstOrFail();

            if (! empty($data['company'])) {
                $company = $profile->company()->firstOr(fn () => Company::create($data['company']));
                $company->fill($data['company'])->save();
                $profile->company_id = $company->id;
            }

            $profile->fill(collect($data)->except(['company', 'sectors', 'stages'])->toArray());
            $profile->onboarding_completed = true;
            $profile->save();

            $profile->sectors()->sync($data['sectors'] ?? []);
            $profile->stages()->sync($data['stages'] ?? []);

            return $profile;
        });

        // Preferences changed — every published project needs rescoring
        // against this investor (spec §22, queued per §115).
        ComputeMatchesForInvestor::dispatch($profile->fresh());

        return back()->with('success', 'Profil mis à jour.');
    }
}
