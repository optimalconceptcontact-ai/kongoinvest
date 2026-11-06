<?php

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use App\Models\Sector;
use App\Models\Stage;
use App\Models\User;
use App\Notifications\ProjectFundingLimitNotification;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\seed;

beforeEach(function () {
    // Seed les rôles si nécessaire
    if (! Role::where('name', 'entrepreneur')->exists()) {
        seed(RolesAndPermissionsSeeder::class);
    }

    $this->entrepreneur = User::factory()->create();
    $this->entrepreneur->assignRole('entrepreneur');

    // Créer des secteurs et stages nécessaires pour la validation
    $this->sector = Sector::factory()->create();
    $this->stage = Stage::factory()->create(['key' => 'growth']);
});

test('entrepreneur cannot create project with funding amount too high in usd', function () {
    $data = [
        'title' => 'Mon Projet',
        'description' => 'Description du projet test',
        'funding_target_minor' => 15_000_000, // 150 000 $ (trop élevé)
        'funding_currency' => 'USD',
        'investment_types' => ['equity', 'debt', 'grant'],
        'sectors' => [$this->sector->id],
        'stages' => [$this->stage->id],
    ];

    $request = StoreProjectRequest::create('/test', 'POST', $data);
    $request->setContainer(app());
    $validator = Validator::make($data, $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('funding_target_minor'))->toBeTrue();
});

test('entrepreneur cannot create project with funding amount too low in usd', function () {
    $data = [
        'title' => 'Mon Projet',
        'description' => 'Description du projet test',
        'funding_target_minor' => 100_000, // 1 000 $ (trop bas)
        'funding_currency' => 'USD',
        'investment_types' => ['equity', 'debt', 'grant'],
        'sectors' => [$this->sector->id],
        'stages' => [$this->stage->id],
    ];

    $request = StoreProjectRequest::create('/test', 'POST', $data);
    $request->setContainer(app());
    $validator = Validator::make($data, $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('funding_target_minor'))->toBeTrue();
});

test('entrepreneur can create project with valid funding amount in usd', function () {
    $data = [
        'title' => 'Mon Projet Valide',
        'description' => 'Description du projet test avec montant valide',
        'funding_target_minor' => 5_000_000, // 50 000 $ (valide)
        'funding_currency' => 'USD',
        'investment_types' => ['equity', 'debt', 'grant'],
        'sectors' => [$this->sector->id],
        'stages' => [$this->stage->id],
    ];

    $request = StoreProjectRequest::create('/test', 'POST', $data);
    $request->setContainer(app());
    $validator = Validator::make($data, $request->rules());

    expect($validator->passes())->toBeTrue()
        ->and($validator->errors()->has('funding_target_minor'))->toBeFalse();
});

test('validation works correctly for cdf currency', function () {
    // Montant trop bas en CDF
    $dataTooLow = [
        'title' => 'Projet CDF Bas',
        'description' => 'Description',
        'funding_target_minor' => 100_000_000, // 1 000 000 CDF (trop bas)
        'funding_currency' => 'CDF',
        'investment_types' => ['equity', 'debt', 'grant'],
        'sectors' => [$this->sector->id],
        'stages' => [$this->stage->id],
    ];

    $requestLow = StoreProjectRequest::create('/test', 'POST', $dataTooLow);
    $requestLow->setContainer(app());
    $validatorLow = Validator::make($dataTooLow, $requestLow->rules());
    expect($validatorLow->fails())->toBeTrue();

    // Montant valide en CDF
    $dataValid = [
        'title' => 'Projet CDF Valide',
        'description' => 'Description',
        'funding_target_minor' => 10_000_000_000, // 100 000 000 CDF (valide)
        'funding_currency' => 'CDF',
        'investment_types' => ['equity', 'debt', 'grant'],
        'sectors' => [$this->sector->id],
        'stages' => [$this->stage->id],
    ];

    $requestValid = StoreProjectRequest::create('/test', 'POST', $dataValid);
    $requestValid->setContainer(app());
    $validatorValid = Validator::make($dataValid, $requestValid->rules());
    expect($validatorValid->passes())->toBeTrue();
});

test('validation works correctly for eur currency', function () {
    // Montant trop élevé en EUR
    $dataTooHigh = [
        'title' => 'Projet EUR Élevé',
        'description' => 'Description',
        'funding_target_minor' => 12_000_000, // 120 000 € (trop élevé)
        'funding_currency' => 'EUR',
        'investment_types' => ['equity', 'debt', 'grant'],
        'sectors' => [$this->sector->id],
        'stages' => [$this->stage->id],
    ];

    $requestHigh = StoreProjectRequest::create('/test', 'POST', $dataTooHigh);
    $requestHigh->setContainer(app());
    $validatorHigh = Validator::make($dataTooHigh, $requestHigh->rules());
    expect($validatorHigh->fails())->toBeTrue();

    // Montant valide en EUR
    $dataValid = [
        'title' => 'Projet EUR Valide',
        'description' => 'Description',
        'funding_target_minor' => 5_000_000, // 50 000 € (valide)
        'funding_currency' => 'EUR',
        'investment_types' => ['equity', 'debt', 'grant'],
        'sectors' => [$this->sector->id],
        'stages' => [$this->stage->id],
    ];

    $requestValid = StoreProjectRequest::create('/test', 'POST', $dataValid);
    $requestValid->setContainer(app());
    $validatorValid = Validator::make($dataValid, $requestValid->rules());
    expect($validatorValid->passes())->toBeTrue();
});

test('entrepreneur cannot update project with amount outside limits', function () {
    $project = Project::factory()->for($this->entrepreneur, 'user')->create([
        'funding_target_minor' => 5_000_000, // 50 000 $ (valide)
        'funding_currency' => 'USD',
    ]);

    $data = [
        'title' => $project->title,
        'description' => $project->description,
        'funding_target_minor' => 20_000_000, // 200 000 $ (trop élevé)
        'funding_currency' => 'USD',
        'investment_types' => ['equity', 'debt', 'grant'],
        'sectors' => [$this->sector->id],
        'stages' => [$this->stage->id],
    ];

    $request = UpdateProjectRequest::create('/test', 'PUT', $data);
    $request->setContainer(app());
    $validator = Validator::make($data, $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('funding_target_minor'))->toBeTrue();
});

test('check funding limits command sends notification to entrepreneurs with projects outside limits', function () {
    Notification::fake();

    // Créer un projet hors limites (non archivé)
    $project = Project::factory()->for($this->entrepreneur, 'user')->create([
        'funding_target_minor' => 20_000_000, // 200 000 $ (hors limites)
        'funding_currency' => 'USD',
        'funding_limit_notified_at' => null,
        'funding_limit_deadline' => null,
        'archived_at' => null,
    ]);

    // Vérifier que le projet est bien hors limites
    expect($project->isOutsideFundingLimits())->toBeTrue();

    // Exécuter la commande
    $exitCode = Artisan::call('projects:check-funding-limits');

    expect($exitCode)->toBe(0);

    // Vérifier que la notification a été envoyée
    Notification::assertSentTo($this->entrepreneur, ProjectFundingLimitNotification::class);
});

test('check funding limits command does not send duplicate notifications', function () {
    Notification::fake();

    // Créer un projet déjà notifié
    $project = Project::factory()->for($this->entrepreneur, 'user')->create([
        'funding_target_minor' => 20_000_000, // 200 000 $ (hors limites)
        'funding_currency' => 'USD',
        'funding_limit_notified_at' => Carbon::now()->subDays(5),
        'funding_limit_deadline' => Carbon::now()->addDays(9),
    ]);

    // Exécuter la commande
    Artisan::call('projects:check-funding-limits');

    // Vérifier qu'aucune notification n'a été envoyée
    Notification::assertNothingSent();
});

test('check funding limits command with dry run does not send notifications', function () {
    Notification::fake();

    // Créer un projet hors limites
    Project::factory()->for($this->entrepreneur, 'user')->create([
        'funding_target_minor' => 20_000_000, // 200 000 $ (hors limites)
        'funding_currency' => 'USD',
        'funding_limit_notified_at' => null,
    ]);

    // Exécuter la commande en mode dry-run
    Artisan::call('projects:check-funding-limits', ['--dry-run' => true]);

    // Vérifier qu'aucune notification n'a été envoyée
    Notification::assertNothingSent();
});

test('check funding limits command resets tracking when project amount is corrected', function () {
    // Créer un projet avec un montant VALIDE mais qui a été notifié auparavant
    // (simulation : l'entrepreneur a corrigé le montant)
    $project = Project::factory()->for($this->entrepreneur, 'user')->create([
        'funding_target_minor' => 5_000_000, // 50 000 $ (valide et dans les limites)
        'funding_currency' => 'USD',
        'funding_limit_notified_at' => Carbon::now()->subDays(7),
        'funding_limit_deadline' => Carbon::now()->addDays(7),
        'archived_at' => null,
    ]);

    // Vérifier que le projet est maintenant dans les limites
    expect($project->isOutsideFundingLimits())->toBeFalse()
        ->and($project->funding_limit_notified_at)->not->toBeNull();

    // Exécuter la commande
    $exitCode = Artisan::call('projects:check-funding-limits');

    expect($exitCode)->toBe(0);
});

test('project model correctly identifies when it is outside funding limits', function () {
    // Projet hors limites
    $projectOutside = Project::factory()->for($this->entrepreneur, 'user')->create([
        'funding_target_minor' => 20_000_000, // 200 000 $ (hors limites)
        'funding_currency' => 'USD',
    ]);

    expect($projectOutside->isOutsideFundingLimits())->toBeTrue();

    // Projet dans les limites
    $projectInside = Project::factory()->for($this->entrepreneur, 'user')->create([
        'funding_target_minor' => 5_000_000, // 50 000 $ (dans les limites)
        'funding_currency' => 'USD',
    ]);

    expect($projectInside->isOutsideFundingLimits())->toBeFalse();
});

test('project model correctly checks if deadline has passed', function () {
    // Deadline dans le futur
    $projectFuture = Project::factory()->for($this->entrepreneur, 'user')->create([
        'funding_limit_deadline' => Carbon::now()->addDays(5),
    ]);

    expect($projectFuture->hasFundingLimitDeadlinePassed())->toBeFalse();

    // Deadline dans le passé
    $projectPast = Project::factory()->for($this->entrepreneur, 'user')->create([
        'funding_limit_deadline' => Carbon::now()->subDays(1),
    ]);

    expect($projectPast->hasFundingLimitDeadlinePassed())->toBeTrue();
});

test('project model correctly determines if warning should be shown', function () {
    // Projet hors limites ET notifié => warning doit être affiché
    $projectWithWarning = Project::factory()->for($this->entrepreneur, 'user')->create([
        'funding_target_minor' => 20_000_000, // hors limites
        'funding_currency' => 'USD',
        'funding_limit_notified_at' => Carbon::now()->subDays(3),
    ]);

    expect($projectWithWarning->shouldShowFundingLimitWarning())->toBeTrue();

    // Projet hors limites MAIS non notifié => pas de warning
    $projectNoNotification = Project::factory()->for($this->entrepreneur, 'user')->create([
        'funding_target_minor' => 20_000_000, // hors limites
        'funding_currency' => 'USD',
        'funding_limit_notified_at' => null,
    ]);

    expect($projectNoNotification->shouldShowFundingLimitWarning())->toBeFalse();

    // Projet dans les limites => pas de warning
    $projectInLimits = Project::factory()->for($this->entrepreneur, 'user')->create([
        'funding_target_minor' => 5_000_000, // dans les limites
        'funding_currency' => 'USD',
        'funding_limit_notified_at' => Carbon::now()->subDays(3),
    ]);

    expect($projectInLimits->shouldShowFundingLimitWarning())->toBeFalse();
});

test('projects within limits are not affected by check command', function () {
    Notification::fake();

    // Créer plusieurs projets dans les limites
    $projects = Project::factory(3)->for($this->entrepreneur, 'user')->create([
        'funding_target_minor' => 5_000_000, // 50 000 $ (valide)
        'funding_currency' => 'USD',
    ]);

    // Exécuter la commande
    Artisan::call('projects:check-funding-limits');

    // Vérifier qu'aucune notification n'a été envoyée
    Notification::assertNothingSent();

    // Vérifier que les projets n'ont pas été modifiés
    foreach ($projects as $project) {
        $project->refresh();
        expect($project->funding_limit_notified_at)->toBeNull()
            ->and($project->funding_limit_deadline)->toBeNull();
    }
});
