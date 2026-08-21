<?php

namespace App\Http\Controllers;

use App\Actions\Documents\RequestDocumentAccess;
use App\Actions\Documents\RespondToDocumentRequest;
use App\Actions\Documents\UploadDocument;
use App\Models\DealRoom;
use App\Models\Document;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DealRoomDocumentController extends Controller
{
    public function store(Request $request, DealRoom $dealRoom, UploadDocument $action): RedirectResponse
    {
        $this->authorize('view', $dealRoom); // participant check — same gate as the deal room itself

        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,docx', 'max:10240'],
            'share_immediately' => ['boolean'],
        ]);

        $action->handle($request->user(), $dealRoom, $data['title'], $data['file'], $data['share_immediately'] ?? false);

        return back()->with('success', 'Document ajouté.');
    }

    public function request(Request $request, Document $document, RequestDocumentAccess $action): RedirectResponse
    {
        $this->authorize('request', $document);

        $action->handle($request->user(), $document);

        return back()->with('success', "Demande d'accès envoyée.");
    }

    public function approve(Request $request, Document $document, RespondToDocumentRequest $action): RedirectResponse
    {
        $this->authorize('manage', $document);

        $action->approve($request->user(), $document);

        return back()->with('success', 'Document partagé.');
    }

    public function reject(Request $request, Document $document, RespondToDocumentRequest $action): RedirectResponse
    {
        $this->authorize('manage', $document);

        $action->reject($request->user(), $document);

        return back()->with('success', 'Demande refusée.');
    }
}
