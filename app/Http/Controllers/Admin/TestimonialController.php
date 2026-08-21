<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TestimonialController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Testimonials/Index', [
            'testimonials' => Testimonial::orderBy('order')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'role' => ['nullable', 'string', 'max:150'],
            'company' => ['nullable', 'string', 'max:150'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'content' => ['required', 'string', 'max:1000'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        // is_published defaults to false. This form exists for the admin
        // to transcribe a real testimonial a real user actually gave —
        // it is not a content generator, and nothing here is ever
        // auto-populated (spec §69).
        Testimonial::create($data);

        return back()->with('success', 'Témoignage créé (non publié).');
    }

    public function update(Request $request, Testimonial $testimonial): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'role' => ['nullable', 'string', 'max:150'],
            'company' => ['nullable', 'string', 'max:150'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'content' => ['required', 'string', 'max:1000'],
            'is_published' => ['boolean'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        $testimonial->update($data);

        return back()->with('success', 'Témoignage mis à jour.');
    }

    public function destroy(Testimonial $testimonial): RedirectResponse
    {
        $testimonial->delete();

        return back()->with('success', 'Témoignage supprimé.');
    }
}
