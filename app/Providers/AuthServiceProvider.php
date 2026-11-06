<?php

namespace App\Providers;

use App\Models\AiAnalysis;
use App\Models\Connection;
use App\Models\Contract;
use App\Models\DealRoom;
use App\Models\Dispute;
use App\Models\Document;
use App\Models\Escrow;
use App\Models\InvestmentContractWorkspace;
use App\Models\KycProfile;
use App\Models\MatchModel;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Subscription;
use App\Models\User;
use App\Policies\AiAnalysisPolicy;
use App\Policies\ConnectionPolicy;
use App\Policies\ContractPolicy;
use App\Policies\DealRoomPolicy;
use App\Policies\DisputePolicy;
use App\Policies\DocumentPolicy;
use App\Policies\EscrowPolicy;
use App\Policies\InvestmentContractWorkspacePolicy;
use App\Policies\KycPolicy;
use App\Policies\MatchPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\ProjectPolicy;
use App\Policies\SubscriptionPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Policies are registered explicitly rather than relying purely on
     * naming-convention auto-discovery — as new domains land (Project,
     * Kyc, Escrow, Contract...) each Policy is added here so the mapping
     * is visible in one place (spec §11).
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
        Project::class => ProjectPolicy::class,
        Subscription::class => SubscriptionPolicy::class,
        MatchModel::class => MatchPolicy::class,
        Connection::class => ConnectionPolicy::class,
        DealRoom::class => DealRoomPolicy::class,
        Document::class => DocumentPolicy::class,
        KycProfile::class => KycPolicy::class,
        Contract::class => ContractPolicy::class,
        Payment::class => PaymentPolicy::class,
        Escrow::class => EscrowPolicy::class,
        Dispute::class => DisputePolicy::class,
        AiAnalysis::class => AiAnalysisPolicy::class,
        InvestmentContractWorkspace::class => InvestmentContractWorkspacePolicy::class,
        // Conversation/Message access is enforced through DealRoomPolicy
        // (a conversation's only participants are its deal room's) —
        // no separate Policy needed for either.
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
