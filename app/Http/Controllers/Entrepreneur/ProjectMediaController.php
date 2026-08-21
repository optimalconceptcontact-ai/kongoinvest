<?php

namespace App\Http\Controllers\Entrepreneur;

use App\Actions\Projects\UploadProjectMedia;
use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProjectMediaController extends Controller
{
    public function store(Request $request, Project $project, UploadProjectMedia $action): RedirectResponse
    {
        $this->authorize('manageMedia', $project);

        $data = $request->validate([
            'collection' => ['required', Rule::in(['project-images', 'project-documents'])],
            'files' => ['required', 'array', 'min:1'],
            'files.*' => $request->input('collection') === 'project-images'
                ? ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'] // 5MB
                : ['file', 'mimes:pdf,doc,docx', 'max:20480'], // 20MB
        ]);

        $action->handle($project, $data['collection'], $data['files']);

        return back()->with('success', 'Fichier(s) ajouté(s).');
    }

    public function destroy(Request $request, Project $project, Media $media): RedirectResponse
    {
        $this->authorize('manageMedia', $project);

        abort_unless($media->model_type === Project::class && $media->model_id === $project->id, 404);

        $media->delete();

        return back()->with('success', 'Fichier supprimé.');
    }
}
