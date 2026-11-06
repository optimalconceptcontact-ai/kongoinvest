<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\KycProfile;
use App\Models\Project;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Spec §43: "Ne jamais exposer directement /storage/kyc/... Utiliser :
 * private disk, authorization, temporary signed URLs, expiration, audit
 * logs." This controller is the only path to a private-disk file in the
 * app. Every route pointing here is wrapped in Laravel's `signed`
 * middleware (see routes/web.php), so a link only works for the window
 * it was generated with — even a leaked link expires. Authorization is
 * re-checked here too, not just when the link was generated, and every
 * successful download is written to audit_logs.
 */
class FileDownloadController extends Controller
{
    public function kycDocument(Request $request, Media $media, AuditLogger $audit): StreamedResponse
    {
        /** @var KycProfile $kycProfile */
        $kycProfile = $media->model;

        Gate::authorize('view', $kycProfile);

        $audit->log($request->user(), 'kyc.document_downloaded', $kycProfile, ['media_id' => $media->id]);

        return $this->stream($media);
    }

    public function dealDocument(Request $request, Media $media, AuditLogger $audit): StreamedResponse
    {
        /** @var Document $document */
        $document = $media->model;

        Gate::authorize('view', $document);

        $audit->log($request->user(), 'document.downloaded', $document, ['media_id' => $media->id]);

        return $this->stream($media);
    }

    public function projectDocument(Request $request, Media $media, AuditLogger $audit): StreamedResponse
    {
        /** @var Project $project */
        $project = $media->model;

        Gate::authorize('view', $project);

        $audit->log($request->user(), 'project.document_downloaded', $project, ['media_id' => $media->id]);

        return $this->stream($media);
    }

    private function stream(Media $media): StreamedResponse
    {
        return response()->streamDownload(function () use ($media) {
            $stream = Storage::disk($media->disk)->readStream($media->getPathRelativeToRoot());
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, $media->file_name);
    }
}
