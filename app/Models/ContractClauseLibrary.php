<?php

namespace App\Models;

use App\Enums\ClauseCategory;
use App\Enums\FinancingComponentType;
use App\Enums\LegalForm;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $slug
 * @property string $title
 * @property ClauseCategory $category
 * @property list<string>|null $instruments
 * @property list<string>|null $legal_forms
 * @property list<string>|null $jurisdictions
 * @property list<string>|null $affected_scopes
 * @property list<string>|null $legal_bases
 * @property list<array<string, mixed>>|null $variants
 * @property bool $requires_legal_review
 * @property bool $is_active
 */
class ContractClauseLibrary extends Model
{
    protected $table = 'contract_clause_library'; // "library" est indénombrable — inférence de table désactivée

    // Bibliothèque de clauses : endpoint d'administration / seeder,
    // seeder, champs librement assignables ici.
    protected $fillable = [
        'slug',
        'title',
        'category',
        'instruments',
        'legal_forms',
        'jurisdictions',
        'affected_scopes',
        'purpose',
        'plain_language_explanation',
        'founder_benefit',
        'investor_benefit',
        'financial_impact',
        'legal_impact',
        'risks',
        'body',
        'variants',
        'legal_bases',
        'requires_legal_review',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'category' => ClauseCategory::class,
            'instruments' => 'array',
            'legal_forms' => 'array',
            'jurisdictions' => 'array',
            'affected_scopes' => 'array',
            'variants' => 'array',
            'legal_bases' => 'array',
            'requires_legal_review' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<ContractClauseVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(ContractClauseVersion::class)->orderByDesc('version');
    }

    /** @return HasMany<ContractClauseSelection, $this> */
    public function selections(): HasMany
    {
        return $this->hasMany(ContractClauseSelection::class);
    }

    /**
     * @param  Builder<ContractClauseLibrary>  $query
     * @return Builder<ContractClauseLibrary>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * La clause est-elle candidate pour cette forme juridique et
     * ce type d'instrument ? Filtre bibliothèque — la sélection
     * finale reste une décision des parties (prompt §5, §16 règle 2).
     */
    public function allowsInstrument(FinancingComponentType $instrument): bool
    {
        $instruments = $this->instruments;

        return $instruments === null || empty($instruments)
            || in_array($instrument->value, $instruments, true);
    }

    public function allowsLegalForm(LegalForm $form): bool
    {
        $forms = $this->legal_forms;

        return $forms === null || empty($forms)
            || in_array($form->value, $forms, true)
            || in_array($form->baseForm()?->value, $forms, true);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function variant(string $key): ?array
    {
        foreach ($this->variants ?? [] as $variant) {
            if (($variant['key'] ?? null) === $key) {
                return $variant;
            }
        }

        return null;
    }
}
