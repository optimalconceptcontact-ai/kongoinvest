<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Kyc\SubmitKyc;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

class KycController extends Controller
{
    public function edit(Request $request): Response
    {
        $kyc = $request->user()->kycProfile()->firstOrFail();
        $this->authorize('view', $kyc);

        return Inertia::render('Settings/Kyc', [
            'status' => $kyc->status->value,
            'rejectionReason' => $kyc->rejection_reason,
            'submittedAt' => $kyc->submitted_at?->toIso8601String(),
            'documents' => $kyc->getMedia('kyc-documents')->map(fn ($m) => [
                'id' => $m->id,
                'name' => $m->name,
                // A real temporary signed URL (spec §43) — valid for 10
                // minutes, re-generated fresh every time this page
                // loads. Even if a link leaks, it's useless shortly
                // after.
                'downloadUrl' => URL::temporarySignedRoute('kyc.documents.download', now()->addMinutes(10), ['media' => $m->id]),
            ]),
        ]);
    }

    public function submit(Request $request, SubmitKyc $action): RedirectResponse
    {
        $kyc = $request->user()->kycProfile()->firstOrFail();
        $this->authorize('submit', $kyc);

        $data = $request->validate([
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'], // 10MB
        ]);

        $action->handle($kyc, $data['files']);

        return back()->with('success', 'Dossier KYC soumis pour vérification.');
    }
}
