<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MatchCriterion;
use App\Http\Controllers\Controller;
use App\Models\MatchingCriteriaWeight;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class MatchingWeightController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/MatchingWeights/Index', [
            'weights' => MatchingCriteriaWeight::all()->map(fn ($w) => [
                'criterion' => $w->criterion,
                'label' => MatchCriterion::from($w->criterion)->label(),
                'weight' => (float) $w->weight,
            ]),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'weights' => ['required', 'array'],
            'weights.*.criterion' => ['required', 'string', 'in:'.implode(',', array_map(fn ($c) => $c->value, MatchCriterion::cases()))],
            'weights.*.weight' => ['required', 'numeric', 'min:0', 'max:1'],
        ]);

        // Spec §23: weights are administrable, but they must still sum
        // to 100% — a nonsensical config (weights summing to 60% or
        // 140%) would silently corrupt every future match score, not
        // just look wrong in the admin UI.
        $sum = round(array_sum(array_column($data['weights'], 'weight')), 3);
        if ($sum !== 1.0) {
            throw ValidationException::withMessages([
                'weights' => "Les pondérations doivent totaliser 100% (actuellement {$sum}).",
            ]);
        }

        DB::transaction(function () use ($data) {
            foreach ($data['weights'] as $row) {
                MatchingCriteriaWeight::updateOrCreate(
                    ['criterion' => $row['criterion']],
                    ['weight' => $row['weight']],
                );
            }
        });

        Cache::forget('matching.weights'); // MatchingWeightsService caches for 10 min — invalidate immediately

        return back()->with('success', 'Pondérations mises à jour. Les prochains calculs de matching les utiliseront.');
    }
}
