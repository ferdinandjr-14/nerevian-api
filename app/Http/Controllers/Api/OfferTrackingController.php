<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesApiRequests;
use App\Http\Controllers\Controller;
use App\Models\EstatOferta;
use App\Models\Oferta;
use App\Models\OfertaTrackingEvent;
use App\Models\TrackingStep;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfferTrackingController extends Controller
{
    use AuthorizesApiRequests;

    public function show(Request $request, Oferta $oferta): JsonResponse
    {
        $user = $this->currentUser($request);
        $this->authorizeOfferAccess($user, $oferta);

        abort_if(! $this->isTrackable($oferta), 409, 'Tracking is only available for active or finalized offers.');

        return response()->json([
            'tracking' => $this->buildTrackingPayload($oferta->fresh()),
        ]);
    }

    public function update(Request $request, Oferta $oferta): JsonResponse
    {
        $user = $this->requireRoles($request, ['commercial', 'admin']);

        abort_if($oferta->loadMissing('estatOferta')->estatOferta?->estat === 'Finalized', 409, 'Finalized offers cannot be updated.');
        abort_if(! $this->isTrackable($oferta), 409, 'Tracking can only be updated for accepted or shipped offers.');

        $validated = $request->validate([
            'tracking_step_id' => ['required', 'integer', 'exists:tracking_steps,id'],
            'observacions' => ['nullable', 'string'],
            'completed_at' => ['nullable', 'date'],
        ]);

        $allowedSteps = $this->allowedSteps($oferta);
        $step = $allowedSteps->firstWhere('id', (int) $validated['tracking_step_id']);

        abort_if(! $step, 422, 'Tracking step is not valid for this incoterm.');

        $lastCompletedOrder = $oferta->trackingEvents()
            ->join('tracking_steps', 'tracking_steps.id', '=', 'oferta_tracking_events.tracking_step_id')
            ->max('tracking_steps.ordre');

        $lastCompletedOrder ??= 0;

        abort_if($step->ordre > $lastCompletedOrder + 1, 422, 'Tracking steps must be updated in order.');

        OfertaTrackingEvent::updateOrCreate(
            [
                'oferta_id' => $oferta->id,
                'tracking_step_id' => $step->id,
            ],
            [
                'updated_by_id' => $user->id,
                'observacions' => $validated['observacions'] ?? null,
                'completed_at' => $validated['completed_at'] ?? now(),
            ]
        );

        $finalStepOrder = $allowedSteps->max('ordre');
        $newStatus = $step->ordre >= $finalStepOrder ? 'Finalized' : 'Shipped';

        $oferta->update([
            'estat_oferta_id' => $this->statusId($newStatus),
        ]);

        return response()->json([
            'message' => 'Tracking actualitzat correctament.',
            'tracking' => $this->buildTrackingPayload($oferta->fresh()),
        ]);
    }

    private function buildTrackingPayload(Oferta $oferta): array
    {
        $oferta->loadMissing([
            'estatOferta',
            'incoterm.tipusIncoterm',
            'incoterm.trackingStep',
            'trackingEvents.trackingStep',
            'trackingEvents.updatedBy',
        ]);

        $events = $oferta->trackingEvents->keyBy('tracking_step_id');
        $steps = $this->allowedSteps($oferta)->map(function (TrackingStep $step) use ($events): array {
            $event = $events->get($step->id);

            return [
                'id' => $step->id,
                'ordre' => $step->ordre,
                'nom' => $step->nom,
                'completed' => $event !== null,
                'completed_at' => $event?->completed_at,
                'observacions' => $event?->observacions,
                'updated_by' => $event?->updatedBy,
            ];
        })->values()->all();

        return [
            'offer_id' => $oferta->id,
            'status' => $oferta->estatOferta?->estat,
            'incoterm' => $oferta->incoterm?->tipusIncoterm,
            'last_tracking_step' => $oferta->incoterm?->trackingStep,
            'steps' => $steps,
        ];
    }

    private function allowedSteps(Oferta $oferta)
    {
        $oferta->loadMissing('incoterm.trackingStep');

        $maxOrder = $oferta->incoterm?->trackingStep?->ordre;

        abort_if($maxOrder === null, 422, 'This offer does not have a valid incoterm tracking configuration.');

        return TrackingStep::query()
            ->where('ordre', '<=', $maxOrder)
            ->orderBy('ordre')
            ->get();
    }

    private function isTrackable(Oferta $oferta): bool
    {
        $status = $oferta->loadMissing('estatOferta')->estatOferta?->estat;

        return in_array($status, ['Accepted', 'Shipped', 'Delayed', 'Finalized'], true);
    }

    private function statusId(string $status): int
    {
        return EstatOferta::query()
            ->where('estat', $status)
            ->valueOrFail('id');
    }
}
