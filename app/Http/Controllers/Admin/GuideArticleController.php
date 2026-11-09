<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GuideArticle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin-only editorial CRUD for the public guide. Articles are written
 * in rich text and rendered as full articles (not FAQ accordions) on
 * their matching guide tab. Authorization comes from the admin route
 * group (role:admin + permission:admin.dashboard), like the other
 * content-management controllers.
 */
class GuideArticleController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Guides/Index', [
            'guides' => GuideArticle::orderBy('section')->orderBy('order')->get(),
            'sections' => collect(GuideArticle::SECTIONS)
                ->map(fn (string $label, string $key) => [
                    'key' => $key,
                    'label' => $label,
                ])
                ->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());

        GuideArticle::create($data + ['is_published' => $data['is_published'] ?? true]);

        return back()->with('success', 'Article du guide ajouté.');
    }

    public function update(Request $request, GuideArticle $guide): RedirectResponse
    {
        $data = $request->validate($this->rules());

        $guide->update($data);

        return back()->with('success', 'Article du guide mis à jour.');
    }

    public function destroy(GuideArticle $guide): RedirectResponse
    {
        $guide->delete();

        return back()->with('success', 'Article du guide supprimé.');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(): array
    {
        return [
            'section' => ['required', 'string', Rule::in(array_keys(GuideArticle::SECTIONS))],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'content' => ['required', 'string', 'max:100000'],
            'order' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['boolean'],
        ];
    }
}
