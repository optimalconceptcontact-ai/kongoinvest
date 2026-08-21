<?php

use App\Enums\DocumentVisibility;
use App\Models\Document;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SubscriptionPlansSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(SubscriptionPlansSeeder::class);
});

function dealRoomWithParticipants(): array
{
    $dealRoom = \App\Models\DealRoom::factory()->create();
    $entrepreneur = $dealRoom->entrepreneur;
    $investor = $dealRoom->investor;

    return [$dealRoom, $entrepreneur, $investor];
}

it('never shows a private document to the other participant until it is shared', function () {
    [$dealRoom, $entrepreneur, $investor] = dealRoomWithParticipants();

    $document = Document::factory()->create([
        'deal_room_id' => $dealRoom->id,
        'owner_id' => $entrepreneur->id,
        'visibility' => DocumentVisibility::Private->value,
    ]);

    expect($document->isVisibleTo($entrepreneur))->toBeTrue();
    expect($document->isVisibleTo($investor))->toBeFalse();

    // The deal room's own data payload must also omit it — not just the
    // policy check (spec §46's visibility states enforced server-side).
    $data = \App\Data\DealRoomData::fromModel($dealRoom->load(['documents', 'entrepreneur', 'investor', 'project']), $investor);
    expect($data->documents)->toBeEmpty();
});

it('reveals a document to both participants once the owner approves a share request', function () {
    [$dealRoom, $entrepreneur, $investor] = dealRoomWithParticipants();

    $document = Document::factory()->create([
        'deal_room_id' => $dealRoom->id,
        'owner_id' => $entrepreneur->id,
        'visibility' => DocumentVisibility::Private->value,
    ]);

    app(\App\Actions\Documents\RequestDocumentAccess::class)->handle($investor, $document);
    expect($document->fresh()->visibility->value)->toBe('requested');

    app(\App\Actions\Documents\RespondToDocumentRequest::class)->approve($entrepreneur, $document);

    $document->refresh();
    expect($document->visibility->value)->toBe('shared');
    expect($document->isVisibleTo($investor))->toBeTrue();
});

it('never lets a user outside the deal room request or approve document access', function () {
    [$dealRoom, $entrepreneur, $investor] = dealRoomWithParticipants();
    $outsider = User::factory()->investor()->create();

    $document = Document::factory()->create([
        'deal_room_id' => $dealRoom->id,
        'owner_id' => $entrepreneur->id,
    ]);

    expect($outsider->can('request', $document))->toBeFalse();
    expect($outsider->can('manage', $document))->toBeFalse();
});
