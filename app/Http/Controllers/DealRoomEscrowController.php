<?php

namespace App\Http\Controllers;

use App\Actions\Escrow\CreateEscrow;
use App\Actions\Escrow\RaiseDispute;
use App\Actions\Escrow\RefundEscrow;
use App\Actions\Escrow\ReleaseEscrow;
use App\Actions\Payments\CreatePayment;
use App\Enums\PaymentProviderName;
use App\Enums\PaymentPurpose;
use App\Models\Contract;
use App\Models\Escrow;
use App\Services\Escrow\AccompanimentFeeCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class DealRoomEscrowController extends Controller
{
    public function store(Request $request, Contract $contract, CreateEscrow $action): RedirectResponse
    {
        $this->authorize('view', $contract); // deal room participant

        $escrow = $action->handle($contract);

        return redirect()->route('escrow.checkout', $escrow)->with('success', 'Séquestre créé — financez-le pour l\'activer.');
    }

    public function checkout(Request $request, Escrow $escrow, AccompanimentFeeCalculator $fees): Response
    {
        $this->authorize('fund', $escrow);

        return Inertia::render('Escrow/Checkout', [
            'escrow' => $escrow->only(['id', 'deal_room_id', 'amount_minor', 'currency']),
            // Coaching accompaniment fee — the app manages the investor/
            // entrepreneur relationship through to closing, and charges a
            // tiered percentage of the investment for it (see
            // AccompanimentFeeCalculator). Charged on top of the contract
            // amount so the entrepreneur still receives the full amount.
            'fee' => $fees->breakdown($escrow->amount_minor, $escrow->currency),
        ]);
    }

    /** Card rail only — see BillingController::intent() for the same pattern applied to subscriptions. */
    public function intent(Request $request, Escrow $escrow, CreatePayment $action, AccompanimentFeeCalculator $fees): JsonResponse
    {
        $this->authorize('fund', $escrow);

        $totalMinor = $fees->breakdown($escrow->amount_minor, $escrow->currency)['totalMinor'];

        try {
            [, $intent] = $action->handle(
                payer: $request->user(),
                purpose: PaymentPurpose::Investment,
                provider: PaymentProviderName::Stripe,
                amountMinor: $totalMinor,
                currency: $escrow->currency,
                payable: $escrow,
                idempotencyKey: "escrow-{$escrow->id}-".now()->format('YmdHisv'),
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => "Le paiement par carte n'est pas disponible pour le moment."], 503);
        }

        return response()->json(['clientSecret' => $intent->clientSecret]);
    }

    public function pay(Request $request, Escrow $escrow, CreatePayment $action, AccompanimentFeeCalculator $fees): RedirectResponse
    {
        $this->authorize('fund', $escrow);

        $data = $request->validate([
            'provider' => ['required', Rule::in(['paypal', 'orange_money', 'mpesa', 'airtel_money'])],
            'phone_number' => ['required_if:provider,orange_money,mpesa,airtel_money', 'nullable', 'string'],
        ]);

        $provider = PaymentProviderName::from($data['provider']);
        $totalMinor = $fees->breakdown($escrow->amount_minor, $escrow->currency)['totalMinor'];

        try {
            [$payment, $intent] = $action->handle(
                payer: $request->user(),
                purpose: PaymentPurpose::Investment,
                provider: $provider,
                amountMinor: $totalMinor,
                currency: $escrow->currency,
                payable: $escrow,
                metadata: array_filter(['phone_number' => $data['phone_number'] ?? null]),
                idempotencyKey: "escrow-{$escrow->id}-".now()->format('YmdHis'),
            );
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['provider' => "Ce moyen de paiement n'est pas disponible pour le moment."]);
        }

        if ($intent->redirectUrl) {
            return redirect()->away($intent->redirectUrl);
        }

        return redirect()->route('escrow.checkout', $escrow)->with('success', 'Paiement initié.');
    }

    public function release(Request $request, Escrow $escrow, ReleaseEscrow $action): RedirectResponse
    {
        $this->authorize('release', $escrow);

        $action->handle($request->user(), $escrow);

        return back()->with('success', 'Fonds libérés.');
    }

    public function requestRefund(Request $request, Escrow $escrow, RefundEscrow $action): RedirectResponse
    {
        $this->authorize('requestRefund', $escrow);

        $action->handle($request->user(), $escrow);

        return back()->with('success', 'Remboursement traité.');
    }

    public function dispute(Request $request, Escrow $escrow, RaiseDispute $action): RedirectResponse
    {
        $this->authorize('dispute', $escrow);

        $data = $request->validate(['reason' => ['required', 'string', 'max:2000']]);

        $action->handle($request->user(), $escrow, $data['reason']);

        return back()->with('success', 'Litige ouvert — notre équipe va l\'examiner.');
    }
}
