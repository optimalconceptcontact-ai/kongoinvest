<?php

namespace App\Actions\Contracts;

use App\Enums\SubscriptionFeature;
use App\Enums\WorkspaceStatus;
use App\Models\Company;
use App\Models\DealRoom;
use App\Models\InvestmentContractWorkspace;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\SubscriptionService;
use Illuminate\Validation\ValidationException;

class CreateInvestmentContractWorkspace
{
    public function __construct(
        private AuditLogger $audit,
        private SubscriptionService $subscriptions,
    ) {}

    /**
     * Ouvre l'espace contractuel d'une opération acceptée — un espace
     * par DealRoom, jamais deux (une salle = une opération). Le
     * créateur porte la fonction « negotiation_tools » de son
     * abonnement : le gating s'appuie sur les données de plan, jamais
     * sur un rôle codé en dur (spec §28).
     *
     * @param  array{company_id?: int|string, currency?: string}  $input
     */
    public function handle(User $creator, DealRoom $dealRoom, array $input = []): InvestmentContractWorkspace
    {
        if (! $this->subscriptions->hasFeature($creator, SubscriptionFeature::NegotiationTools)) {
            throw ValidationException::withMessages([
                'workspace' => "L'abonnement actuel n'inclut pas les outils de négociation : l'espace contractuel requiert la fonction « negotiation_tools ».",
            ]);
        }

        if (InvestmentContractWorkspace::query()->where('deal_room_id', $dealRoom->id)->exists()) {
            throw ValidationException::withMessages([
                'deal_room_id' => 'Un espace contractuel existe déjà pour cette salle de négociation : une salle ne porte qu\'une opération.',
            ]);
        }

        if (! $dealRoom->isParticipant($creator)) {
            throw ValidationException::withMessages([
                'workspace' => 'Seuls les participants de la salle de négociation peuvent ouvrir l\'espace contractuel.',
            ]);
        }

        $companyId = $input['company_id'] ?? null;

        if ($companyId !== null && ! Company::query()->whereKey($companyId)->exists()) {
            throw ValidationException::withMessages([
                'company_id' => "L'entreprise rattachée à l'opération est introuvable.",
            ]);
        }

        // Devise explicite à trois lettres — défaut USD (scénario de
        // référence §7), mais jamais une devise implicite non tracée
        // quand l'appelant en passe une.
        $currency = strtoupper((string) ($input['currency'] ?? 'USD'));

        if (! preg_match('/^[A-Z]{3}$/', $currency)) {
            throw ValidationException::withMessages([
                'currency' => 'La devise doit être un code à trois lettres (ex. USD, CDF).',
            ]);
        }

        // Champs hors $fillable à dessein (état et propriété) —
        // forceCreate() est le seul chemin d'écriture, comme pour
        // Contract dans GenerateContract.
        $workspace = InvestmentContractWorkspace::forceCreate([
            'deal_room_id' => $dealRoom->id,
            'company_id' => $companyId,
            'created_by_user_id' => $creator->id,
            'status' => WorkspaceStatus::Draft->value,
            'currency' => $currency,
            'last_activity_at' => now(),
        ]);

        $this->audit->log($creator, 'contract_workspace.created', $workspace, [
            'deal_room_id' => $dealRoom->id,
            'currency' => $currency,
        ]);

        return $workspace->fresh();
    }
}
