<?php

namespace App\Http\Controllers\Public;

use App\Data\FaqData;
use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FaqController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Faq::published();

        if ($search = $request->string('q')->trim()->value()) {
            $query->where(fn ($q) => $q
                ->where('question', 'like', "%{$search}%")
                ->orWhere('answer', 'like', "%{$search}%"));
        }

        $faqs = $query->get()->map(fn (Faq $f) => FaqData::fromModel($f));

        return Inertia::render('Public/Faq', [
            'faqs' => $faqs,
            'categories' => $faqs->pluck('category')->unique()->values(),
            'filters' => $request->only('q'),
        ]);
    }
}
