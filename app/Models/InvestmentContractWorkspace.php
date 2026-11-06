<?php

namespace App\Models;

use App\Enums\FinancingCategory;
use App\Enums\FounderControlPreference;
use App\Enums\NegotiationStatus;
use App\Enums\WorkspaceStatus;
use Database\Factories\InvestmentContractWorkspaceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $deal_room_id
 * @property int|null $company_id
 * @property int $created_by_user_id
 * @property int|null $legal_verified_by_user_id
 * @property string $currency
 * @property WorkspaceStatus $status
 * @property FinancingCategory|null $financing_category
 * @property FounderControlPreference|null $founder_control_preference
 * @property array<string, mixed>|null $investor_position
 * @property int|null $pre_money_minor
 * @property int|null $post_money_minor
 * @property int|null $total_investment_minor
 * @property int|null $founder_share_bps
 * @property int|null $investor_share_bps
 * @property array<string, mixed>|null $post_operation_cap_table
 * @property Carbon|null $entrepreneur_analysis_consent_at
 * @property Carbon|null $investor_analysis_consent_at
 * @property Carbon|null $legal_reviewed_at
 * @property Carbon|null $closed_at
 * @property Carbon|null $last_activity_at
 */
class InvestmentContractWorkspace extends Model
{
    /** @use HasFactory<InvestmentContractWorkspaceFactory> */
    use HasFactory;

    protected $fillable = []; // every field is set exclusively via Actions — app/Actions/Contracts/**

    protected function casts(): array
    {
        return [
            'status' => WorkspaceStatus::class,
            'financing_category' => FinancingCategory::class,
            'founder_control_preference' => FounderControlPreference::class,
            'investor_position' => 'array',
            'pre_money_minor' => 'integer',
            'post_money_minor' => 'integer',
            'total_investment_minor' => 'integer',
            'founder_share_bps' => 'integer',
            'investor_share_bps' => 'integer',
            'post_operation_cap_table' => 'array',
            'entrepreneur_analysis_consent_at' => 'datetime',
            'investor_analysis_consent_at' => 'datetime',
            'legal_reviewed_at' => 'datetime',
            'closed_at' => 'datetime',
            'last_activity_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<DealRoom, $this> */
    public function dealRoom(): BelongsTo
    {
        return $this->belongsTo(DealRoom::class);
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function legalVerifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'legal_verified_by_user_id');
    }

    /** @return HasMany<ContractNegotiation, $this> */
    public function negotiations(): HasMany
    {
        return $this->hasMany(ContractNegotiation::class)->orderBy('round');
    }

    /** @return HasOne<ContractNegotiation, $this> */
    public function currentNegotiation(): HasOne
    {
        return $this->hasOne(ContractNegotiation::class)
            ->ofMany('round')
            ->where('status', NegotiationStatus::Open->value);
    }

    /** @return HasMany<ContractFinancingComponent, $this> */
    public function financingComponents(): HasMany
    {
        return $this->hasMany(ContractFinancingComponent::class)->orderBy('sort_order');
    }

    /** @return HasMany<NegotiationConversationSnapshot, $this> */
    public function snapshots(): HasMany
    {
        return $this->hasMany(NegotiationConversationSnapshot::class)->orderByDesc('version');
    }

    /** @return HasMany<NegotiationTerm, $this> */
    public function terms(): HasMany
    {
        return $this->hasMany(NegotiationTerm::class);
    }

    /** @return HasMany<ContractClauseSelection, $this> */
    public function clauseSelections(): HasMany
    {
        return $this->hasMany(ContractClauseSelection::class)->orderBy('sort_order');
    }

    /** @return HasMany<ContractDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(ContractDocument::class);
    }

    /** @return HasMany<ContractCondition, $this> */
    public function conditions(): HasMany
    {
        return $this->hasMany(ContractCondition::class);
    }

    /** @return HasMany<ContractSignature, $this> */
    public function signatures(): HasMany
    {
        return $this->hasMany(ContractSignature::class);
    }

    /** @return HasMany<LegalComplianceCheck, $this> */
    public function legalChecks(): HasMany
    {
        return $this->hasMany(LegalComplianceCheck::class);
    }

    /** @return HasMany<FinancialConsistencyCheck, $this> */
    public function financialChecks(): HasMany
    {
        return $this->hasMany(FinancialConsistencyCheck::class);
    }

    // --- applicability / guards ---------------------------------

    public function isParticipant(User $user): bool
    {
        return $this->dealRoom->isParticipant($user);
    }

    public function founder(): ?User
    {
        return $this->dealRoom->entrepreneur; // l'entrepreneur est le fondateur au sens contractuel
    }

    public function investor(): ?User
    {
        return $this->dealRoom->investor;
    }

    public function isFounder(User $user): bool
    {
        return $this->dealRoom->entrepreneur_user_id === $user->id;
    }

    public function isInvestor(User $user): bool
    {
        return $this->dealRoom->investor_user_id === $user->id;
    }

    /**
     * Côté contractuel d'un utilisateur : « founder » pour
     * l'entrepreneur de la salle, « investor » pour l'investisseur.
     * `null` pour un tiers — mapping déduit des ids de la salle,
     * jamais libre (décisions, approbations, signatures).
     */
    public function sideOf(User $user): ?string
    {
        if ($this->isFounder($user)) {
            return 'founder';
        }

        if ($this->isInvestor($user)) {
            return 'investor';
        }

        return null;
    }

    /**
     * Analysable tant que chaque partie a consenti à l'analyse
     * de la conversation (prompt §14). Jamais sans double
     * consentement explicite et traçé.
     */
    public function conversationAnalysisConsented(): bool
    {
        return $this->entrepreneur_analysis_consent_at !== null
            && $this->investor_analysis_consent_at !== null;
    }

    public function hasConsented(User $user): bool
    {
        if ($this->isFounder($user)) {
            return $this->entrepreneur_analysis_consent_at !== null;
        }

        if ($this->isInvestor($user)) {
            return $this->investor_analysis_consent_at !== null;
        }

        return false;
    }

    public function isClosed(): bool
    {
        return $this->status === WorkspaceStatus::Closed;
    }
}
