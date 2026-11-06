<?php

namespace App\Http\Controllers;

use App\Actions\Messages\SendMessage;
use App\Data\DealRoomData;
use App\Models\DealRoom;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DealRoomController extends Controller
{
    public function show(Request $request, DealRoom $dealRoom): Response
    {
        // The entire enforcement of "a user outside the Deal Room cannot
        // read the messages" (spec §135) lives in this one authorize()
        // call — DealRoomPolicy::view() checks participant membership,
        // nothing else grants access, not even the project owner's
        // other Policies.
        $this->authorize('view', $dealRoom);

        return Inertia::render('DealRoom/Show', [
            'dealRoom' => DealRoomData::fromModel($dealRoom->load([
                'project', 'entrepreneur', 'investor', 'conversation.messages.sender',
                'documents.owner', 'documents.media', 'contracts.versions', 'contractWorkspace',
            ]), $request->user()),
            'viewerRole' => $request->user()->id === $dealRoom->entrepreneur_user_id ? 'entrepreneur' : 'investor',
        ]);
    }

    public function sendMessage(Request $request, DealRoom $dealRoom, SendMessage $action): RedirectResponse
    {
        $this->authorize('sendMessage', $dealRoom);

        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);

        $conversation = $dealRoom->conversation()->firstOrFail();

        $action->handle($request->user(), $conversation, $data['body']);

        return back();
    }
}
