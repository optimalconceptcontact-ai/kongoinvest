<?php

namespace App\Providers;

use App\Contracts\AiConversationProviderInterface;
use App\Contracts\ContractDraftProviderInterface;
use App\Contracts\ProjectAnalysisProviderInterface;
use App\Events\ConnectionAccepted;
use App\Events\ConnectionRequested;
use App\Events\ContractAccepted;
use App\Events\EscrowDisputed;
use App\Events\EscrowFunded;
use App\Events\EscrowRefunded;
use App\Events\EscrowReleased;
use App\Events\KycApproved;
use App\Events\KycRejected;
use App\Events\MatchCreated;
use App\Events\PaymentSucceeded;
use App\Listeners\ActivateSubscriptionOnPayment;
use App\Listeners\FundEscrowOnPaymentSucceeded;
use App\Listeners\LogAuthenticationEvents;
use App\Listeners\SendConnectionAcceptedNotification;
use App\Listeners\SendConnectionRequestNotification;
use App\Listeners\SendContractNotification;
use App\Listeners\SendEscrowNotification;
use App\Listeners\SendKycNotification;
use App\Listeners\SendMatchNotification;
use App\Listeners\SendPaymentNotification;
use App\Listeners\SyncPlanSubscriptionFromCashier;
use App\Services\Ai\Providers\AnthropicContractDraftProvider;
use App\Services\Ai\Providers\AnthropicConversationProvider;
use App\Services\Ai\Providers\AnthropicProjectAnalysisProvider;
use App\Services\Ai\Providers\NullAiConversationProvider;
use App\Services\Ai\Providers\NullContractDraftProvider;
use App\Services\Ai\Providers\NullProjectAnalysisProvider;
use App\Services\Matching\AiSemanticAnalyzer;
use App\Services\Matching\AnthropicSemanticAnalyzer;
use App\Services\Matching\NullSemanticAnalyzer;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Events\WebhookHandled;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // The one place that decides which AiSemanticAnalyzer
        // implementation the rest of the app gets — MatchScoringService
        // depends on the interface only, so flipping MATCHING_AI_ENABLED
        // never requires touching matching logic.
        $this->app->bind(AiSemanticAnalyzer::class, function () {
            return config('matching.ai_enabled')
                ? $this->app->make(AnthropicSemanticAnalyzer::class)
                : $this->app->make(NullSemanticAnalyzer::class);
        });

        // Same pattern, separate toggle (AI_FEATURES_ENABLED): a
        // deployment might want matching AI off while project
        // analysis/coach/advisor are on, or vice versa — these are
        // product decisions, not one on/off switch for "AI" as a whole.
        $this->app->bind(ProjectAnalysisProviderInterface::class, function () {
            return config('ai.enabled')
                ? $this->app->make(AnthropicProjectAnalysisProvider::class)
                : $this->app->make(NullProjectAnalysisProvider::class);
        });

        $this->app->bind(AiConversationProviderInterface::class, function () {
            return config('ai.enabled')
                ? $this->app->make(AnthropicConversationProvider::class)
                : $this->app->make(NullAiConversationProvider::class);
        });

        $this->app->bind(ContractDraftProviderInterface::class, function () {
            return config('ai.enabled')
                ? $this->app->make(AnthropicContractDraftProvider::class)
                : $this->app->make(NullContractDraftProvider::class);
        });
    }

    public function boot(): void
    {
        // Generated asset URLs (Spatie Media Library conversions, storage
        // links, signed routes) inherit the APP_URL scheme. If a prod
        // box is reached over HTTPS while APP_URL says http://, pages
        // load but every media URL becomes mixed content and gets
        // blocked/upgraded by browsers. Forcing HTTPS in production makes
        // the scheme mismatch impossible; local dev (Herd/artisan serve)
        // keeps whatever scheme it actually runs on.
        URL::forceHttps(app()->isProduction());

        // Payment
        Event::listen(PaymentSucceeded::class, FundEscrowOnPaymentSucceeded::class);
        Event::listen(PaymentSucceeded::class, SendPaymentNotification::class);
        // PayPal/Mobile Money subscription rail only — see BillingController::pay().
        Event::listen(PaymentSucceeded::class, ActivateSubscriptionOnPayment::class);

        // Subscription billing (Cashier) — card rail. Fires after Cashier's
        // own WebhookController has synced its subscriptions table; this
        // just mirrors the result into the app's own plan-tier table.
        Event::listen(WebhookHandled::class, SyncPlanSubscriptionFromCashier::class);

        // Matching & Connections — every one of these events has existed
        // since Phase 5/8 with a comment promising "Phase 13 attaches a
        // notification here" (spec §116). This is that phase.
        Event::listen(MatchCreated::class, SendMatchNotification::class);
        Event::listen(ConnectionRequested::class, SendConnectionRequestNotification::class);
        Event::listen(ConnectionAccepted::class, SendConnectionAcceptedNotification::class);

        // KYC
        Event::listen(KycApproved::class, [SendKycNotification::class, 'handleApproved']);
        Event::listen(KycRejected::class, [SendKycNotification::class, 'handleRejected']);

        // Contracts
        Event::listen(ContractAccepted::class, SendContractNotification::class);

        // Escrow
        Event::listen(EscrowFunded::class, [SendEscrowNotification::class, 'handleFunded']);
        Event::listen(EscrowReleased::class, [SendEscrowNotification::class, 'handleReleased']);
        Event::listen(EscrowRefunded::class, [SendEscrowNotification::class, 'handleRefunded']);
        Event::listen(EscrowDisputed::class, [SendEscrowNotification::class, 'handleDisputed']);

        // Authentication audit trail (spec §49) — Fortify fires these
        // standard Laravel events on every login/logout; we only listen.
        Event::listen(Login::class, [LogAuthenticationEvents::class, 'handleLogin']);
        Event::listen(Logout::class, [LogAuthenticationEvents::class, 'handleLogout']);
    }
}
