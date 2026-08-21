<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DealRoomContractController;
use App\Http\Controllers\DealRoomController;
use App\Http\Controllers\DealRoomDocumentController;
use App\Http\Controllers\DealRoomEscrowController;
use App\Http\Controllers\Entrepreneur;
use App\Http\Controllers\FileDownloadController;
use App\Http\Controllers\Investor;
use App\Http\Controllers\Public as PublicControllers;
use App\Http\Controllers\Settings;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [PublicControllers\HomeController::class, 'index'])->name('home');

Route::get('/about', fn () => Inertia::render('Public/About'))->name('about');
Route::get('/pricing', [PublicControllers\PricingController::class, 'index'])->name('pricing');
Route::get('/faq', [PublicControllers\FaqController::class, 'index'])->name('faq');
Route::get('/how-it-works', fn () => Inertia::render('Public/HowItWorks'))->name('how-it-works');
Route::get('/projects', [PublicControllers\ProjectController::class, 'index'])->name('projects.index');
Route::get('/projects/{project:slug}', [PublicControllers\ProjectController::class, 'show'])->name('projects.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [\App\Http\Controllers\NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllRead'])->name('notifications.read-all');

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/profile', [Settings\ProfileController::class, 'edit'])->name('profile.edit');
        Route::delete('/profile', [Settings\ProfileController::class, 'destroy'])->name('profile.destroy');

        Route::get('/subscription', [Settings\SubscriptionController::class, 'edit'])->name('subscription.edit');
        Route::put('/subscription', [Settings\SubscriptionController::class, 'update'])->name('subscription.update');

        Route::get('/subscription/{plan}/checkout', [Settings\BillingController::class, 'checkout'])->name('billing.checkout');
        Route::post('/subscription/{plan}/checkout', [Settings\BillingController::class, 'pay'])->name('billing.pay');
        Route::post('/subscription/{plan}/checkout/intent', [Settings\BillingController::class, 'intent'])->name('billing.intent');

        Route::get('/kyc', [Settings\KycController::class, 'edit'])->name('kyc.edit');
        Route::post('/kyc', [Settings\KycController::class, 'submit'])->name('kyc.submit');

        Route::middleware('role:entrepreneur')->group(function () {
            Route::get('/entrepreneur-profile', [Settings\EntrepreneurProfileController::class, 'edit'])->name('entrepreneur-profile.edit');
            Route::put('/entrepreneur-profile', [Settings\EntrepreneurProfileController::class, 'update'])->name('entrepreneur-profile.update');
        });

        Route::middleware('role:investor')->group(function () {
            Route::get('/investor-profile', [Settings\InvestorProfileController::class, 'edit'])->name('investor-profile.edit');
            Route::put('/investor-profile', [Settings\InvestorProfileController::class, 'update'])->name('investor-profile.update');
        });
    });

    // Role-specific route groups are added as each dashboard is built —
    // see spec §106. Each still requires its own Policy check; the
    // `role:` middleware below is a coarse gate, not the authorization
    // layer itself (spec §8).
    Route::middleware('role:entrepreneur')->prefix('entrepreneur')->name('entrepreneur.')->group(function () {
        Route::resource('projects', Entrepreneur\ProjectController::class)->except(['show'])->scoped(['project' => 'slug']);
        Route::post('/projects/{project:slug}/publish', [Entrepreneur\ProjectController::class, 'publish'])->name('projects.publish');
        Route::post('/projects/{project:slug}/archive', [Entrepreneur\ProjectController::class, 'archive'])->name('projects.archive');
        Route::post('/projects/{project:slug}/media', [Entrepreneur\ProjectMediaController::class, 'store'])->name('projects.media.store');
        Route::delete('/projects/{project:slug}/media/{media}', [Entrepreneur\ProjectMediaController::class, 'destroy'])->name('projects.media.destroy');
        Route::get('/matches', [Entrepreneur\MatchController::class, 'index'])->name('matches.index');
        Route::get('/connections', [Entrepreneur\ConnectionController::class, 'index'])->name('connections.index');
        Route::post('/connections/{connection}/accept', [Entrepreneur\ConnectionController::class, 'accept'])->name('connections.accept');
        Route::post('/connections/{connection}/reject', [Entrepreneur\ConnectionController::class, 'reject'])->name('connections.reject');
        Route::post('/projects/{project}/ai-coach', [Entrepreneur\AiCoachController::class, 'ask'])->name('ai-coach.ask');
    });

    Route::middleware('role:investor')->prefix('investor')->name('investor.')->group(function () {
        Route::get('/matches', [Investor\MatchController::class, 'index'])->name('matches.index');
        Route::post('/matches/recompute', [Investor\MatchController::class, 'recompute'])->name('matches.recompute');
        Route::post('/matches/{match}/viewed', [Investor\MatchController::class, 'markViewed'])->name('matches.viewed');
        Route::post('/matches/{match}/dismiss', [Investor\MatchController::class, 'dismiss'])->name('matches.dismiss');
        Route::get('/connections', [Investor\ConnectionController::class, 'index'])->name('connections.index');
        Route::post('/projects/{project}/connections', [Investor\ConnectionController::class, 'store'])->name('connections.store');
        Route::post('/connections/{connection}/cancel', [Investor\ConnectionController::class, 'cancel'])->name('connections.cancel');
        Route::post('/projects/{project}/ai-advisor', [Investor\AiAdvisorController::class, 'ask'])->name('ai-advisor.ask');
    });

    // AI project analysis (spec §50) — available to anyone who can view
    // the project, not scoped to a role like Coach/Advisor are.
    Route::post('/projects/{project}/ai-analysis', [\App\Http\Controllers\ProjectAnalysisController::class, 'store'])->name('projects.ai-analysis.store');

    // Shared between both roles — DealRoomPolicy alone decides access,
    // not which role: group the route sits under (spec §44's
    // participant list includes both sides equally).
    Route::get('/deal-rooms/{dealRoom}', [DealRoomController::class, 'show'])->name('deal-rooms.show');
    Route::post('/deal-rooms/{dealRoom}/messages', [DealRoomController::class, 'sendMessage'])->name('deal-rooms.messages.store');
    Route::post('/deal-rooms/{dealRoom}/documents', [DealRoomDocumentController::class, 'store'])->name('deal-rooms.documents.store');
    Route::post('/documents/{document}/request', [DealRoomDocumentController::class, 'request'])->name('documents.request');
    Route::post('/documents/{document}/approve', [DealRoomDocumentController::class, 'approve'])->name('documents.approve');
    Route::post('/documents/{document}/reject', [DealRoomDocumentController::class, 'reject'])->name('documents.reject');
    Route::post('/deal-rooms/{dealRoom}/contracts', [DealRoomContractController::class, 'store'])->name('deal-rooms.contracts.store');
    Route::post('/contracts/{contract}/accept', [DealRoomContractController::class, 'accept'])->name('contracts.accept');
    Route::post('/contracts/{contract}/reject', [DealRoomContractController::class, 'reject'])->name('contracts.reject');

    Route::post('/contracts/{contract}/escrow', [DealRoomEscrowController::class, 'store'])->name('escrow.store');
    Route::get('/escrow/{escrow}/checkout', [DealRoomEscrowController::class, 'checkout'])->name('escrow.checkout');
    Route::post('/escrow/{escrow}/checkout', [DealRoomEscrowController::class, 'pay'])->name('escrow.pay');
    Route::post('/escrow/{escrow}/checkout/intent', [DealRoomEscrowController::class, 'intent'])->name('escrow.intent');
    Route::post('/escrow/{escrow}/release', [DealRoomEscrowController::class, 'release'])->name('escrow.release');
    Route::post('/escrow/{escrow}/refund', [DealRoomEscrowController::class, 'requestRefund'])->name('escrow.refund');
    Route::post('/escrow/{escrow}/dispute', [DealRoomEscrowController::class, 'dispute'])->name('escrow.dispute');

    // Signed + temporary (spec §43) — these are never linked to directly;
    // every downloadUrl in Inertia props is generated fresh via
    // URL::temporarySignedRoute() and expires in 10 minutes. The
    // `signed` middleware rejects any request whose signature doesn't
    // match or has expired, before the controller even runs.
    Route::middleware('signed')->group(function () {
        Route::get('/kyc/documents/{media}/download', [FileDownloadController::class, 'kycDocument'])->name('kyc.documents.download');
        Route::get('/deal-rooms/documents/{media}/download', [FileDownloadController::class, 'dealDocument'])->name('deal-rooms.documents.download');
        Route::get('/projects/documents/{media}/download', [FileDownloadController::class, 'projectDocument'])->name('projects.documents.download');
    });

    Route::middleware(['role:admin', 'permission:admin.dashboard'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/users', [Admin\UserController::class, 'index'])->name('users.index');
        Route::resource('sectors', Admin\SectorController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('stages', Admin\StageController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('partners', Admin\PartnerController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('testimonials', Admin\TestimonialController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('faqs', Admin\FaqController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('/matching-weights', [Admin\MatchingWeightController::class, 'index'])->name('matching-weights.index');
        Route::put('/matching-weights', [Admin\MatchingWeightController::class, 'update'])->name('matching-weights.update');
        Route::get('/kyc', [Admin\KycController::class, 'index'])->name('kyc.index');
        Route::post('/kyc/{kycProfile}/approve', [Admin\KycController::class, 'approve'])->name('kyc.approve');
        Route::post('/kyc/{kycProfile}/reject', [Admin\KycController::class, 'reject'])->name('kyc.reject');
        Route::get('/disputes', [Admin\DisputeController::class, 'index'])->name('disputes.index');
        Route::post('/disputes/{dispute}/resolve', [Admin\DisputeController::class, 'resolve'])->name('disputes.resolve');
        Route::middleware('signed')->get('/kyc/documents/{media}/download', [FileDownloadController::class, 'kycDocument'])->name('kyc.documents.download');
    });
});

// Webhooks — deliberately outside `auth`/`web`'s CSRF requirement (see
// bootstrap/app.php for the CSRF exclusion). Every provider's own
// handleWebhook() verifies a cryptographic signature before anything is
// trusted (spec §40) — this is not the same thing as "unprotected."
Route::prefix('webhooks')->name('webhooks.')->group(function () {
    Route::post('/stripe', [\App\Http\Controllers\Webhooks\WebhookController::class, 'stripe'])->name('stripe');
    Route::post('/paypal', [\App\Http\Controllers\Webhooks\WebhookController::class, 'paypal'])->name('paypal');
    Route::post('/orange-money', [\App\Http\Controllers\Webhooks\WebhookController::class, 'orangeMoney'])->name('orange-money');
    Route::post('/mpesa', [\App\Http\Controllers\Webhooks\WebhookController::class, 'mpesa'])->name('mpesa');
    Route::post('/airtel-money', [\App\Http\Controllers\Webhooks\WebhookController::class, 'airtelMoney'])->name('airtel-money');
});

require __DIR__.'/auth.php';
