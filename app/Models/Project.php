<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use App\Enums\ProjectVisibility;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Project extends Model implements HasMedia
{
    /** @use HasFactory<\Database\Factories\ProjectFactory> */
    use HasFactory, HasSlug, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'title', 'description', 'executive_summary', 'problem', 'solution',
        'business_model', 'market', 'competitive_advantage', 'team', 'traction',
        'financial_information', 'impact', 'risks',
        'funding_target_minor', 'funding_min_minor', 'funding_max_minor', 'funding_currency',
        'investment_types', 'provinces', 'cities', 'website', 'social_links',
    ];

    // `user_id`, `country_code`, `status`, `visibility`, `published_at`,
    // `archived_at` are deliberately NOT mass-assignable. They're set
    // exclusively by CreateProject / PublishProject / ArchiveProject
    // actions and ownership is always taken from auth()->user(), never
    // from request input (spec §12, §100).

    protected function casts(): array
    {
        return [
            // Raw DrcProvince enum values — normally one, up to 3 when
            // the project's stage is "idea" (see StoreProjectRequest).
            'provinces' => 'array',
            // One city normally, several when spread across multiple
            // provinces (Idea stage) — same reasoning as `provinces`.
            'cities' => 'array',
            // Exactly 3 App\Enums\FundingType values the entrepreneur is
            // open to — the investor later narrows to one when
            // requesting AI contract generation (see ContractDraftService).
            'investment_types' => 'array',
            'status' => ProjectStatus::class,
            'visibility' => ProjectVisibility::class,
            'social_links' => 'array',
            'funding_target_minor' => 'integer',
            'funding_min_minor' => 'integer',
            'funding_max_minor' => 'integer',
            'funding_raised_minor' => 'integer',
            'published_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->usingSeparator('-')
            ->slugsShouldBeNoLongerThan(80);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sectors(): BelongsToMany
    {
        return $this->belongsToMany(Sector::class);
    }

    public function stages(): BelongsToMany
    {
        return $this->belongsToMany(Stage::class);
    }

    public function matches(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(MatchModel::class);
    }

    public function aiAnalyses(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AiAnalysis::class)->latest();
    }

    public function registerMediaCollections(): void
    {
        // Public — served directly, shown on the project detail page.
        $this->addMediaCollection('project-images')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->useDisk('public');

        // Private — pitch decks, financials, etc. Never served directly;
        // always through a signed URL after an authorization check
        // (spec §43, §46).
        $this->addMediaCollection('project-documents')
            ->acceptsMimeTypes(['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
            ->useDisk('private');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(400)
            ->height(300)
            ->performOnCollections('project-images');
    }

    public function scopePublished($query)
    {
        return $query->where('status', ProjectStatus::Published);
    }

    public function scopeVisibleTo($query, ?User $viewer)
    {
        // Public projects are visible to everyone. Anything else
        // currently requires ownership or admin — matched_investors and
        // connection_only will widen once Matching (Phase 5) and
        // Connections (Phase 8) exist to define "matched" / "connected".
        return $query->where(function ($q) use ($viewer) {
            $q->where('visibility', ProjectVisibility::Public);

            if ($viewer) {
                $q->orWhere('user_id', $viewer->id);

                if ($viewer->can('projects.view-all')) {
                    $q->orWhereNotNull('id');
                }
            }
        });
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->user_id === $user->id;
    }
}
