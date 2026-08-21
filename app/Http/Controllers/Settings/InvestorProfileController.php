<?php

namespace App\Http\Controllers\Settings;

use App\Data\InvestorProfileData;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateInvestorProfileRequest;
use App\Jobs\ComputeMatchesForInvestor;
use App\Models\Company;
use App\Models\Sector;
use App\Models\Stage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class InvestorProfileController extends Controller
{
    public function edit(UpdateInvestorProfileRequest $request): Response
    {
        $profile = $request->user()->investorProfile()->with(['company', 'sectors', 'stages'])->firstOrFail();

        return Inertia::render('Settings/InvestorProfile', [
            'profile' => InvestorProfileData::fromModel($profile),
            'sectors' => Sector::where('is_active', true)->orderBy('order')->get()->map(fn ($s) => \App\Data\SectorData::fromModel($s)),
            'stages' => Stage::where('is_active', true)->orderBy('order')->get()->map(fn ($s) => \App\Data\StageData::fromModel($s)),
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
