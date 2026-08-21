<?php

namespace App\Http\Controllers;

use App\Actions\Contracts\RespondToContract;
use App\Enums\FundingType;
use App\Models\Contract;
use App\Models\DealRoom;
use App\Services\Ai\ContractDraftService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DealRoomContractController extends Controller
{
    public function store(Request $request, DealRoom $dealRoom, ContractDraftService $drafter): RedirectResponse
    {
        $this->authorize('generate', [Contract::class, $dealRoom]);

        // Only one of the entrepreneur's 3 declared financing types may
        // be requested — never an arbitrary one.
        $data = $request->validate([
            'investment_type' => ['required', Rule::in($dealRoom->project->investment_types ?? [])],
        ]);

        $drafter->generate($request->user(), $dealRoom, FundingType::from($data['investment_type']));

        return back()->with('success', 'Contrat généré par IA. Les deux parties doivent maintenant l\'accepter.');
    }

    public function accept(Request $request, Contract $contract, RespondToContract $action): RedirectResponse
    {
        $this->authorize('accept', $contract);

        $action->accept($request->user(), $contract);

        return back()->with('success', 'Contrat accepté de votre côté.');
    }

    public function reject(Request $request, Contract $contract, RespondToContract $action): RedirectResponse
    {
        $this->authorize('reject', $contract);

        $action->reject($request->user(), $contract);

        return back()->with('success', 'Contrat refusé.');
    }
}
