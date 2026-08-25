<?php

use App\Actions\Escrow\RaiseDispute;
use App\Models\Connection;
use App\Models\Escrow;
use App\Models\Project;
use App\Models\User;
use App\Notifications\ConnectionAcceptedNotification;
use App\Notifications\ConnectionRequestNotification;
use App\Notifications\EscrowNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SubscriptionPlansSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(SubscriptionPlansSeeder::class);
});

it('notifies the entrepreneur when an investor requests a connection', function () {
    Notification::fake();

    $entrepreneur = User::factory()->entrepreneur()->create();
    $investor = User::factory()->investor()->create();
    $project = Project::factory()->for($entrepreneur)->published()->withTaxonomy()->create();

    $this->actingAs($investor)->post("/investor/projects/{$project->id}/connections");

    Notification::assertSentTo($entrepreneur, ConnectionRequestNotification::class);
});

it('notifies the investor once their connection is accepted', function () {
    Notification::fake();

    $entrepreneur = User::factory()->entrepreneur()->create();
    $investor = User::factory()->investor()->create();
    $project = Project::factory()->for($entrepreneur)->published()->withTaxonomy()->create();

    $this->actingAs($investor)->post("/investor/projects/{$project->id}/connections");
    $connection = Connection::firstOrFail();
    $this->actingAs($entrepreneur)->post("/entrepreneur/connections/{$connection->id}/accept");

    Notification::assertSentTo($investor, ConnectionAcceptedNotification::class);
});

it('actually persists a real, readable notification a user can mark as read - never someone else\'s', function () {
    $entrepreneur = User::factory()->entrepreneur()->create();
    $investor = User::factory()->investor()->create();
    $project = Project::factory()->for($entrepreneur)->published()->withTaxonomy()->create();

    $this->actingAs($investor)->post("/investor/projects/{$project->id}/connections");

    expect($entrepreneur->notifications()->count())->toBe(1);
    $notification = $entrepreneur->notifications()->first();
    expect($notification->read_at)->toBeNull();

    // The investor (not the recipient) tries to mark it read — scoped
    // through $user->notifications(), so this simply finds nothing and
    // does nothing, never touches the entrepreneur's row.
    $this->actingAs($investor)->post("/notifications/{$notification->id}/read");
    expect($notification->fresh()->read_at)->toBeNull();

    // The actual recipient can mark it read. Must stay a 204: the bell
    // fires this POST right before navigating to the notification's
    // target URL — a redirect (back()) would make Inertia follow it and
    // bounce the user off the target page back to where they came from.
    $this->actingAs($entrepreneur)
        ->post("/notifications/{$notification->id}/read")
        ->assertNoContent();
    expect($notification->fresh()->read_at)->not->toBeNull();
});

it('notifies both parties when a dispute is opened', function () {
    Notification::fake();

    $escrow = Escrow::factory()->held()->create();

    app(RaiseDispute::class)->handle($escrow->investor, $escrow, 'Issue with delivery.');

    Notification::assertSentTo($escrow->entrepreneur, EscrowNotification::class);
    Notification::assertSentTo($escrow->investor, EscrowNotification::class);
});
