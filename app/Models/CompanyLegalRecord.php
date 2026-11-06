<?php

namespace App\Models;

use App\Enums\DataVerificationStatus;
use App\Enums\LegalForm;
use Database\Factories\CompanyLegalRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompanyLegalRecord extends Model
{
    /** @use HasFactory<CompanyLegalRecordFactory> */
    use HasFactory;

    /** @use HasFactory<CompanyLegalRecordFactory> */
    use HasFactory;

    protected $fillable = []; // every field is set exclusively via trusted Actions / seeders — app/Actions/**

    protected function casts(): array
    {
        return [
            'legal_form' => LegalForm::class,
            'verification' => DataVerificationStatus::class,
            'capital_minor' => 'integer',
            'share_count' => 'integer',
            'nominal_value_minor' => 'integer',
            'statutes_date' => 'date',
            'beneficial_owners' => 'array',
            'sectorial_authorizations' => 'array',
            'is_current' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Accesseur déterministe : lit la valeur brute et la mappe
     * explicitement — indépendant de l'inférence du cast et
     * tolérant aux valeurs historiques libres de Company.
     */
    public function form(): LegalForm
    {
        return LegalForm::tryFrom((string) $this->attributes['legal_form']) ?? LegalForm::Unknown;
    }

    public function representative(): BelongsTo
    {
        return $this->belongsTo(User::class, 'representative_user_id');
    }

    public function shareholdings(): HasMany
    {
        // Les associés sont rattachés à l'ENTREPRISE, pas à la
        // fiche juridique — la fiche est un instantané courant.
        return $this->hasMany(CompanyShareholding::class, 'company_id', 'company_id');
    }

    /**
     * Structure de détention du capital : déduite des associés
     * réellement enregistrés — SARLU est une SARL avec UN associé
     * (prompt §3.A), jamais un cas d'enum distinct.
     */
    public function isSingleMember(): ?bool
    {
        $holders = $this->shareholdings;

        if (count($holders) === 0) {
            return null; // structure non renseignée : à ne jamais inventer
        }

        return count($holders) === 1;
    }

    /**
     * Libellé de présentation SARLU/SASU/SARL/SAS : la forme de
     * base complétée par la structure de détention réelle. Une
     * divergence entre la forme enregistrée et la structure des
     * associés est signalée, jamais corrigée silencieusement.
     */
    public function presentationLabel(): string
    {
        $single = $this->isSingleMember();
        $form = $this->form();

        if ($single === null) {
            return $form->label();
        }

        if ($form->isSingleMemberForm() !== $single) {
            return $form->label().' ('.($single ? 'unipersonnelle' : 'pluripersonnelle').' — divergence à vérifier)';
        }

        return $form->label().($single ? ' (unipersonnelle)' : ' (pluripersonnelle)');
    }
}
