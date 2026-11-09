<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Kyc\ReviewKyc;
use App\Http\Controllers\Controller;
use App\Models\KycProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

class KycController extends Controller
{
    public function index(): Response
    {
        $profiles = KycProfile::whereIn('status', ['submitted', 'under_review'])
            ->with('user')
            ->oldest('submitted_at')
            ->get();

        return Inertia::render('Admin/Kyc/Index', [
            'profiles' => $profiles->map(fn (KycProfile $k) => [
                'id' => $k->id,
                'userName' => $k->user->name,
                'userEmail' => $k->user->email,
                'status' => $k->status->value,
                'submittedAt' => $k->submitted_at?->toIso8601String(),
                'documents' => $k->getMedia('kyc-documents')->map(fn ($m) => [
                    'id' => $m->id,
                    'name' => $m->name,
                    'downloadUrl' => URL::temporarySignedRoute('kyc.documents.download', now()->addMinutes(10), ['media' => $m->id]),
                ]),
            ]),
        ]);
    }

    public function approve(Request $request, KycProfile $kycProfile, ReviewKyc $action): RedirectResponse
    {
        // The admin route group is a coarse gate only — the per-resource
        // KycPolicy::review check decides who may actually review.
        $this->authorize('review', KycProfile::class);

        $action->approve($request->user(), $kycProfile);

        return back()->with('success', 'KYC approuvé.');
    }

    public function reject(Request $request, KycProfile $kycProfile, ReviewKyc $action): RedirectResponse
    {
        $this->authorize('review', KycProfile::class);

        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);

        $action->reject($request->user(), $kycProfile, $data['reason']);

        return back()->with('success', 'KYC rejeté.');
    }
}
