<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\FinancialInstrument;
use App\Models\FinancingChannel;
use App\Models\FinancingSource;
use App\Models\FinancingType;
use App\Models\GuideArticle;
use App\Models\Stage;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GuideController extends Controller
{
    public function index(Request $request): Response
    {
        $financingTypes = FinancingType::where('is_active', true)
            ->orderBy('sort_order')
            ->with([
                'instruments' => fn ($q) => $q
                    ->where('is_active', true)
                    ->orderBy('sort_order'),
            ])
            ->get()
            ->map(fn (FinancingType $t) => [
                'id' => $t->id,
                'name' => $t->name,
                'slug' => $t->slug,
                'code' => $t->code,
                'description' => $t->description,
                'icon' => $t->icon,
                'color' => $t->color,
                'instruments' => $t->instruments->map(fn (FinancialInstrument $i) => [
                    'id' => $i->id,
                    'name' => $i->name,
                    'slug' => $i->slug,
                    'code' => $i->code,
                    'description' => $i->description,
                    'instrument_category' => $i->instrument_category,
                    'repayment_required' => $i->repayment_required,
                    'ownership_dilution' => $i->ownership_dilution,
                    'conversion_possible' => $i->conversion_possible,
                    'collateral_possible' => $i->collateral_possible,
                    'remuneration_type' => $i->remuneration_type,
                ]),
            ]);

        $instruments = FinancialInstrument::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (FinancialInstrument $i) => [
                'id' => $i->id,
                'name' => $i->name,
                'slug' => $i->slug,
                'code' => $i->code,
                'description' => $i->description,
                'instrument_category' => $i->instrument_category,
                'repayment_required' => $i->repayment_required,
                'ownership_dilution' => $i->ownership_dilution,
                'conversion_possible' => $i->conversion_possible,
                'collateral_possible' => $i->collateral_possible,
                'remuneration_type' => $i->remuneration_type,
            ]);

        $stages = Stage::where('is_active', true)
            ->orderBy('order')
            ->get()
            ->map(fn (Stage $s) => [
                'id' => $s->id,
                'key' => $s->key,
                'name' => $s->name,
                'description' => $s->description,
                'order' => $s->order,
            ]);

        $financingSources = FinancingSource::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (FinancingSource $s) => [
                'id' => $s->id,
                'name' => $s->name,
                'slug' => $s->slug,
                'code' => $s->code,
                'description' => $s->description,
                'source_category' => $s->source_category,
            ]);

        $financingChannels = FinancingChannel::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (FinancingChannel $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'code' => $c->code,
                'description' => $c->description,
                'requires_intermediary' => $c->requires_intermediary,
            ]);

        $guideArticles = GuideArticle::published()
            ->get()
            ->map(fn (GuideArticle $a) => [
                'id' => $a->id,
                'section' => $a->section,
                'title' => $a->title,
                'description' => $a->description,
                'content' => $a->content,
            ]);

        return Inertia::render('Public/Guide', [
            'financingTypes' => $financingTypes,
            'instruments' => $instruments,
            'stages' => $stages,
            'financingSources' => $financingSources,
            'financingChannels' => $financingChannels,
            'guideArticles' => $guideArticles,
            'activeTab' => $request->string('tab', 'financing-types')->value(),
        ]);
    }
}
