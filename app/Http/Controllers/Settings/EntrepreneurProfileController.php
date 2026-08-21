<?php

namespace App\Http\Controllers\Settings;

use App\Data\EntrepreneurProfileData;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateEntrepreneurProfileRequest;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class EntrepreneurProfileController extends Controller
{
    public function edit(UpdateEntrepreneurProfileRequest $request): Response
    {
        // authorize() on the FormRequest already confirmed this is an
        // entrepreneur; the profile row is guaranteed to exist because
        // CreateNewUser creates it at registration (spec §7).
        $profile = $request->user()->entrepreneurProfile()->with('company')->firstOrFail();

        return Inertia::render('Settings/EntrepreneurProfile', [
            'profile' => EntrepreneurProfileData::fromModel($profile),
        ]);
    }

    public function update(UpdateEntrepreneurProfileRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($request, $data) {
            $profile = $request->user()->entrepreneurProfile()->firstOrFail();

            if (! empty($data['company'])) {
                $company = $profile->company()->firstOr(fn () => Company::create($data['company'] + ['name' => $data['company']['name']]));
                $company->fill($data['company'])->save();
                $profile->company_id = $company->id;
            }

            $profile->fill(collect($data)->except('company')->toArray());
            $profile->onboarding_completed = true;
            $profile->save();
        });

        return back()->with('success', 'Profil mis à jour.');
    }
}
