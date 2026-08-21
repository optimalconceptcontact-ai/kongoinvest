<?php

namespace App\Data;

use App\Models\DealRoom;
use App\Models\User;
use Spatie\LaravelData\Data;

class DealRoomData extends Data
{
    public function __construct(
        public int $id,
        public string $projectTitle,
        public string $projectSlug,
        /** @var string[] */
        public array $projectInvestmentTypes,
        public string $entrepreneurName,
        public string $investorName,
        public string $status,
        /** @var MessageData[] */
        public array $messages,
        /** @var array<int, array> */
        public array $documents,
        /** @var array<int, array> */
        public array $contracts,
        /** @var array<string, mixed>|null */
        public ?array $escrow,
    ) {}

    public static function fromModel(DealRoom $dealRoom, User $viewer): self
    {
        $conversation = $dealRoom->conversation;

        return new self(
            id: $dealRoom->id,
            projectTitle: $dealRoom->project->title,
            projectSlug: $dealRoom->project->slug,
            projectInvestmentTypes: $dealRoom->project->investment_types ?? [],
            entrepreneurName: $dealRoom->entrepreneur->name,
            investorName: $dealRoom->investor->name,
            status: $dealRoom->status,
            messages: $conversation
                ? $conversation->messages->map(fn ($m) => MessageData::fromModel($m))->all()
                : [],
            // A private document the other participant hasn't shared
            // yet must never even appear in this list — not just be
            // undownloadable. isVisibleTo() is the same check
            // DocumentPolicy::view() uses, applied here before the data
            // ever leaves the server (spec §46's visibility states are
            // enforced here, not left to the frontend to hide).
            documents: $dealRoom->documents
                ->filter(fn ($d) => $d->isVisibleTo($viewer))
                ->map(fn ($d) => [
                    'id' => $d->id,
                    'title' => $d->title,
                    'visibility' => $d->visibility->value,
                    'ownerId' => $d->owner_id,
                    'ownerName' => $d->owner->name,
                    'requestedByUserId' => $d->requested_by_user_id,
                    'downloadUrl' => $d->getFirstMedia('deal-documents')
                        ? \Illuminate\Support\Facades\URL::temporarySignedRoute('deal-rooms.documents.download', now()->addMinutes(10), ['media' => $d->getFirstMedia('deal-documents')->id])
                        : null,
                ])->values()->all(),
            contracts: $dealRoom->contracts()->latest()->get()->map(fn ($c) => [
                'id' => $c->id,
                'status' => $c->status->value,
                'investmentType' => $c->investment_type->value,
                'amountMinor' => $c->amount_minor,
                'currency' => $c->currency,
                'terms' => $c->terms,
                'entrepreneurAcceptedAt' => $c->entrepreneur_accepted_at?->toIso8601String(),
                'investorAcceptedAt' => $c->investor_accepted_at?->toIso8601String(),
                'latestVersionContent' => $c->latestVersion()?->content,
            ])->all(),
            escrow: self::escrowPayload($dealRoom),
        );
    }

    private static function escrowPayload(DealRoom $dealRoom): ?array
    {
        // hasOneThrough(Escrow, Contract) — a deal room may have generated
        // several Contract rows over time (one per version/negotiation
        // round), so this always reflects the most recently created escrow.
        $escrow = \App\Models\Escrow::where('deal_room_id', $dealRoom->id)->latest()->first();

        if (! $escrow) {
            return null;
        }

        $openDispute = $escrow->openDispute();

        return [
            'id' => $escrow->id,
            'status' => $escrow->status->value,
            'amountMinor' => $escrow->amount_minor,
            'currency' => $escrow->currency,
            'fundedAt' => $escrow->funded_at?->toIso8601String(),
            'releasedAt' => $escrow->released_at?->toIso8601String(),
            'refundedAt' => $escrow->refunded_at?->toIso8601String(),
            'openDispute' => $openDispute ? ['id' => $openDispute->id, 'status' => $openDispute->status->value] : null,
        ];
    }
}
