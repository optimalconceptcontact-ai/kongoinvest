<?php

namespace App\Models;

use App\Enums\BusinessStatus;
use App\Enums\ProjectMaturityStage;
use App\Enums\ProjectStatus;
use App\Enums\ProjectVisibility;
use App\Services\FundingLimitService;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Project extends Model implements HasMedia
{
    /** @use HasFactory<ProjectFactory> */
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
            // List of {full_name, role, linkedin, email, phone} — see
            // StoreProjectRequest for the shape validated on write.
            'team' => 'array',
            'status' => ProjectStatus::class,
            'maturity_stage' => ProjectMaturityStage::class,
            'business_status' => BusinessStatus::class,
            'visibility' => ProjectVisibility::class,
            'social_links' => 'array',
            'funding_target_minor' => 'integer',
            'funding_min_minor' => 'integer',
            'funding_max_minor' => 'integer',
            'funding_raised_minor' => 'integer',
            'funding_limit_notified_at' => 'datetime',
            'funding_limit_deadline' => 'datetime',
            'published_at' => 'datetime',
            'submitted_at' => 'datetime',
            'moderated_at' => 'datetime',
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

    public function moderatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by_id');
    }

    public function complianceCase(): BelongsTo
    {
        return $this->belongsTo(ComplianceCase::class);
    }

    public function formationReadiness(): HasOne
    {
        return $this->hasOne(ProjectFormationReadiness::class);
    }

    public function sectors(): BelongsToMany
    {
        return $this->belongsToMany(Sector::class);
    }

    public function stages(): BelongsToMany
    {
        return $this->belongsToMany(Stage::class);
    }

    public function financingRequests(): HasMany
    {
        return $this->hasMany(FinancingRequest::class);
    }

    public function latestFinancingRequest(): HasOne
    {
        return $this->hasOne(FinancingRequest::class)->latestOfMany();
    }

    public function matches(): HasMany
    {
        return $this->hasMany(MatchModel::class);
    }

    public function aiAnalyses(): HasMany
    {
        return $this->hasMany(AiAnalysis::class)->latest();
    }

    public function savedBy(): HasMany
    {
        return $this->hasMany(SavedProject::class);
    }

    public function isSavedBy(User $user): bool
    {
        return $this->savedBy()->where('investor_user_id', $user->id)->exists();
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
        // Guests cannot view any projects.
        if (! $viewer) {
            return $query->whereRaw('0 = 1');
        }

        // Entrepreneurs can only view their own projects.
        if ($viewer->isEntrepreneur()) {
            return $query->where('user_id', $viewer->id);
        }

        // Public projects are visible to other authenticated users (investors, admins).
        return $query->where(function ($q) use ($viewer) {
            $q->where('visibility', ProjectVisibility::Public);

            $q->orWhere('user_id', $viewer->id);

            if ($viewer->can('projects.view-all')) {
                $q->orWhereNotNull('id');
            }
        });
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->user_id === $user->id;
    }

    /**
     * Vérifie si le montant de financement du projet dépasse les limites autorisées.
     *
     * @return bool True si le funding_target_minor est hors des limites définies pour la devise
     */
    public function isOutsideFundingLimits(): bool
    {
        if ($this->funding_target_minor === null) {
            return false;
        }

        $service = app(FundingLimitService::class);

        return ! $service->isWithinLimits($this->funding_target_minor, $this->funding_currency);
    }

    /**
     * Vérifie si la deadline de correction des limites de financement est dépassée.
     *
     * @return bool True si la deadline existe et est dans le passé
     */
    public function hasFundingLimitDeadlinePassed(): bool
    {
        if ($this->funding_limit_deadline === null) {
            return false;
        }

        return $this->funding_limit_deadline->isPast();
    }

    /**
     * Détermine si un avertissement de limite de financement doit être affiché.
     *
     * Un avertissement est affiché si le projet est hors limites ET a été notifié.
     *
     * @return bool True si un warning doit être affiché à l'entrepreneur
     */
    public function shouldShowFundingLimitWarning(): bool
    {
        return $this->isOutsideFundingLimits() && $this->funding_limit_notified_at !== null;
    }
}
