<?php

namespace App\Data;

use App\Enums\ProjectStatus;
use App\Enums\ProjectVisibility;
use App\Models\Project;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;

class ProjectData extends Data
{
    public function __construct(
        public int $id,
        public string $title,
        public string $slug,
        public string $description,
        public ProjectStatus $status,
        public ProjectVisibility $visibility,
        /** @var string[] */
        public array $investmentTypes,
        /** @var string[] */
        public array $provinces,
        /** @var string[] */
        public array $cities,
        public ?int $fundingTargetMinor,
        public ?int $fundingRaisedMinor,
        public string $fundingCurrency,
        public string $entrepreneurName,
        /** @var SectorData[] */
        public array $sectors,
        /** @var StageData[] */
        public array $stages,
        public ?string $coverImageUrl,
        public bool $isOwner,
        // Full narrative fields are lazy — the discovery list/card never
        // needs them, only the detail page does (avoids shipping 8
        // paragraphs of text per card in a paginated list).
        public string|Lazy|null $executiveSummary = null,
        public string|Lazy|null $problem = null,
        public string|Lazy|null $solution = null,
        public string|Lazy|null $businessModel = null,
        public string|Lazy|null $market = null,
        public string|Lazy|null $competitiveAdvantage = null,
        /** @var array|Lazy */
        public array|Lazy $team = [],
        public string|Lazy|null $traction = null,
        public string|Lazy|null $financialInformation = null,
        public string|Lazy|null $impact = null,
        public string|Lazy|null $risks = null,
        /** @var array<string, mixed>|null */
        public ?array $fundingLimitWarning = null,
        /** @var array<string, mixed>|null */
        public ?array $financingRequest = null,
        public ?string $moderationNotes = null,
        public ?string $submittedAt = null,
    ) {}

    public static function fromModel(Project $project, bool $full = false): self
    {
        $lazy = fn (?string $value) => $full ? $value : Lazy::create(fn () => $value);
        $lazyTeam = fn (array $value) => $full ? $value : Lazy::create(fn () => $value);

        $financingRequest = null;
        if ($full) {
            $latestRequest = $project->latestFinancingRequest()->with(['components.instrument.financingTypes', 'components.terms'])->first();
            if ($latestRequest) {
                $financingRequest = [
                    'id' => $latestRequest->id,
                    'title' => $latestRequest->title,
                    'totalAmountMinor' => $latestRequest->total_amount_minor,
                    'currency' => $latestRequest->currency,
                    'minAcceptableMinor' => $latestRequest->min_acceptable_minor,
                    'equityDilutionPercentageTarget' => $latestRequest->equity_dilution_percentage_target,
                    'preferredChannelId' => $latestRequest->preferred_channel_id,
                    'status' => $latestRequest->status,
                    'notes' => $latestRequest->notes,
                    'components' => $latestRequest->components->map(fn ($c) => [
                        'id' => $c->id,
                        'financialInstrumentId' => $c->financial_instrument_id,
                        'instrumentCode' => $c->instrument?->code,
                        'instrumentName' => $c->instrument?->name,
                        'targetAmountMinor' => $c->target_amount_minor,
                        'minAmountMinor' => $c->min_amount_minor,
                        'isMandatory' => $c->is_mandatory,
                        'status' => $c->status,
                        'terms' => $c->terms ? [
                            'valuationPreMoneyMinor' => $c->terms->valuation_pre_money_minor,
                            'interestRateBps' => $c->terms->interest_rate_bps,
                            'durationMonths' => $c->terms->duration_months,
                            'gracePeriodMonths' => $c->terms->grace_period_months,
                            'revenueSharePercentage' => $c->terms->revenue_share_percentage,
                            'capMinor' => $c->terms->cap_minor,
                            'discountRateBps' => $c->terms->discount_rate_bps,
                            'collateralRequired' => $c->terms->collateral_required,
                        ] : null,
                    ])->all(),
                ];
            }
        }

        return new self(
            id: $project->id,
            title: $project->title,
            slug: $project->slug,
            description: $project->description,
            status: $project->status,
            visibility: $project->visibility,
            // Raw DrcProvince values (e.g. 'kongo_central') — the frontend
            // maps to a label; keeping this raw is what makes the edit
            // form's province select able to pre-select the current value.
            investmentTypes: $project->investment_types ?? [],
            provinces: $project->provinces ?? [],
            cities: $project->cities ?? [],
            fundingTargetMinor: $project->funding_target_minor,
            fundingRaisedMinor: $project->funding_raised_minor,
            fundingCurrency: $project->funding_currency,
            entrepreneurName: $project->user->name,
            sectors: $project->sectors->map(fn ($s) => SectorData::fromModel($s))->all(),
            stages: $project->stages->map(fn ($s) => StageData::fromModel($s))->all(),
            coverImageUrl: $project->getFirstMediaUrl('project-images', 'thumb') ?: null,
            isOwner: auth()->check() && $project->isOwnedBy(auth()->user()),
            executiveSummary: $lazy($project->executive_summary),
            problem: $lazy($project->problem),
            solution: $lazy($project->solution),
            businessModel: $lazy($project->business_model),
            market: $lazy($project->market),
            competitiveAdvantage: $lazy($project->competitive_advantage),
            team: $lazyTeam($project->team ?? []),
            traction: $lazy($project->traction),
            financialInformation: $lazy($project->financial_information),
            impact: $lazy($project->impact),
            risks: $lazy($project->risks),
            financingRequest: $financingRequest,
            moderationNotes: $project->moderation_notes,
            submittedAt: $project->submitted_at?->toIso8601String(),
        );
    }
}
