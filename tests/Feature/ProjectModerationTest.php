<?php

use App\Enums\ProjectStatus;
use App\Enums\ProjectVisibility;
use App\Jobs\ComputeMatchesForProject;
use App\Models\Project;
use App\Models\User;
use App\Notifications\ProjectModerationNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SubscriptionPlansSeeder;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(SubscriptionPlansSeeder::class);

    $this->entrepreneur = User::factory()->entrepreneur()->create();
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    Queue::fake();
});

it('lets an entrepreneur submit a complete draft for review instead of publishing it', function () {
    $project = Project::factory()->for($this->entrepreneur)->withTaxonomy()->create([
        'status' => ProjectStatus::Draft->value,
        'funding_target_minor' => 250_000,
    ]);

    $this->actingAs($this->entrepreneur)
        ->post("/entrepreneur/projects/{$project->slug}/submit")
        ->assertRedirect();

    $project->refresh();

    expect($project->status)->toBe(ProjectStatus::PendingReview)
        ->and($project->submitted_at)->not->toBeNull()
        ->and($project->published_at)->toBeNull();

    Queue::assertNotPushed(ComputeMatchesForProject::class);
});

it('refuses to submit an incomplete project', function () {
    $project = Project::factory()->for($this->entrepreneur)->create([
        'status' => ProjectStatus::Draft->value,
        'funding_target_minor' => null,
    ]);

    $this->actingAs($this->entrepreneur)
        ->post("/entrepreneur/projects/{$project->slug}/submit")
        ->assertSessionHasErrors('publish');

    expect($project->fresh()->status)->toBe(ProjectStatus::Draft);
});

it('lets an administrator approve a pending project and triggers matching', function () {
    Notification::fake();

    $project = Project::factory()->for($this->entrepreneur)->withTaxonomy()->create([
        'status' => ProjectStatus::PendingReview->value,
        'visibility' => ProjectVisibility::Private->value,
        'funding_target_minor' => 250_000,
        'submitted_at' => now(),
    ]);

    $this->actingAs($this->admin)
        ->post("/admin/projects/{$project->slug}/approve")
        ->assertRedirect();

    $project->refresh();

    expect($project->status)->toBe(ProjectStatus::Published)
        ->and($project->visibility)->toBe(ProjectVisibility::Public)
        ->and($project->published_at)->not->toBeNull()
        ->and($project->moderated_at)->not->toBeNull()
        ->and($project->moderated_by_id)->toBe($this->admin->id)
        ->and($project->moderation_notes)->toBeNull();

    Queue::assertPushed(ComputeMatchesForProject::class);
    Notification::assertSentTo($this->entrepreneur, ProjectModerationNotification::class);
});

it('lets an administrator reject a pending project with a reason and notifies the entrepreneur', function () {
    Notification::fake();

    $project = Project::factory()->for($this->entrepreneur)->withTaxonomy()->create([
        'status' => ProjectStatus::PendingReview->value,
        'funding_target_minor' => 250_000,
        'submitted_at' => now(),
    ]);

    $this->actingAs($this->admin)
        ->post("/admin/projects/{$project->slug}/reject", [])
        ->assertSessionHasErrors('reason');

    expect($project->fresh()->status)->toBe(ProjectStatus::PendingReview);

    $this->actingAs($this->admin)
        ->post("/admin/projects/{$project->slug}/reject", [
            'reason' => 'Le plan financier doit être détaillé.',
        ])
        ->assertRedirect();

    $project->refresh();

    expect($project->status)->toBe(ProjectStatus::Rejected)
        ->and($project->moderation_notes)->toBe('Le plan financier doit être détaillé.')
        ->and($project->moderated_by_id)->toBe($this->admin->id);

    Notification::assertSentTo(
        $this->entrepreneur,
        ProjectModerationNotification::class,
        fn (ProjectModerationNotification $notification) => $notification->approved === false
            && $notification->notes === 'Le plan financier doit être détaillé.',
    );
});

it('lets an entrepreneur correct and resubmit a rejected project', function () {
    $project = Project::factory()->for($this->entrepreneur)->withTaxonomy()->create([
        'status' => ProjectStatus::Rejected->value,
        'funding_target_minor' => 250_000,
        'moderation_notes' => 'À compléter.',
        'moderated_at' => now(),
        'moderated_by_id' => $this->admin->id,
    ]);

    $this->actingAs($this->entrepreneur)
        ->post("/entrepreneur/projects/{$project->slug}/submit")
        ->assertRedirect();

    $project->refresh();

    expect($project->status)->toBe(ProjectStatus::PendingReview)
        ->and($project->moderation_notes)->toBeNull()
        ->and($project->moderated_at)->toBeNull()
        ->and($project->moderated_by_id)->toBeNull();
});

it('refuses approval to an administrator without projects.moderate', function () {
    $project = Project::factory()->withTaxonomy()->create([
        'status' => ProjectStatus::PendingReview->value,
        'funding_target_minor' => 250_000,
    ]);

    Role::findByName('admin')->revokePermissionTo('projects.moderate');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->actingAs($this->admin->fresh())
        ->post("/admin/projects/{$project->slug}/approve")
        ->assertForbidden();

    expect($project->fresh()->status)->toBe(ProjectStatus::PendingReview);
});
