<?php

namespace App\Http\Controllers\Api\Admin;

use App\Classes\Utilitat;
use App\Http\Controllers\Concerns\AuthorizesApiRequests;
use App\Http\Controllers\Controller;
use App\Models\Incoterm;
use App\Models\Oferta;
use App\Models\TipusIncoterm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Throwable;

class IncotermController extends Controller
{
    use AuthorizesApiRequests;

    public function index(Request $request): JsonResponse
    {
        $this->requireRoles($request, ['admin']);

        $incoterms = TipusIncoterm::query()
            ->with(['trackingSteps'])
            ->orderBy('codi')
            ->get()
            ->map(fn (TipusIncoterm $incoterm) => $this->formatIncoterm($incoterm))
            ->values();

        return response()->json([
            'incoterms' => $incoterms,
        ]);
    }

    public function show(Request $request, TipusIncoterm $incoterm): JsonResponse
    {
        $this->requireRoles($request, ['admin']);

        return response()->json([
            'incoterm' => $this->formatIncoterm($incoterm->load('trackingSteps')),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->requireRoles($request, ['admin']);

        $validated = $request->validate($this->rules());

        DB::beginTransaction();

        try {
            $incoterm = TipusIncoterm::create([
                'codi' => $validated['codi'],
                'nom' => $validated['nom'],
            ]);

            $incoterm->trackingSteps()->sync($validated['tracking_step_ids']);

            DB::commit();

            return response()->json([
                'message' => 'Incoterm created successfully.',
                'incoterm' => $this->formatIncoterm($incoterm->load('trackingSteps')),
            ], 201);
        } catch (Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => Utilitat::errorMessage($e, 'No se ha podido crear el incoterm.'),
            ], 500);
        }
    }

    public function update(Request $request, TipusIncoterm $incoterm): JsonResponse
    {
        $this->requireRoles($request, ['admin']);

        $validated = $request->validate($this->rules($incoterm));

        DB::beginTransaction();

        try {
            $incoterm->update([
                'codi' => $validated['codi'],
                'nom' => $validated['nom'],
            ]);

            $incoterm->trackingSteps()->sync($validated['tracking_step_ids']);

            DB::commit();

            return response()->json([
                'message' => 'Incoterm updated successfully.',
                'incoterm' => $this->formatIncoterm($incoterm->load('trackingSteps')),
            ]);
        } catch (Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => Utilitat::errorMessage($e, 'No se ha podido actualizar el incoterm.'),
            ], 500);
        }
    }

    public function destroy(Request $request, TipusIncoterm $incoterm): JsonResponse
    {
        $this->requireRoles($request, ['admin']);

        DB::beginTransaction();

        try {
            $incotermIds = $incoterm->incoterms()->pluck('id');

            if ($incotermIds->isNotEmpty()) {
                Oferta::query()
                    ->whereIn('incoterm_id', $incotermIds)
                    ->update(['incoterm_id' => null]);

                Incoterm::query()
                    ->whereIn('id', $incotermIds)
                    ->delete();
            }

            $incoterm->delete();

            DB::commit();

            return response()->json([
                'message' => 'Incoterm deleted successfully.',
            ]);
        } catch (Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => Utilitat::errorMessage($e, 'No se ha podido eliminar el incoterm.'),
            ], 500);
        }
    }

    private function rules(?TipusIncoterm $incoterm = null): array
    {
        return [
            'codi' => [
                'required',
                'string',
                'max:50',
                Rule::unique('tipus_incoterms', 'codi')->ignore($incoterm?->id),
            ],
            'nom' => ['required', 'string', 'max:255'],
            'tracking_step_ids' => ['required', 'array', 'min:1'],
            'tracking_step_ids.*' => ['required', 'integer', 'distinct', 'exists:tracking_steps,id'],
        ];
    }

    private function formatIncoterm(TipusIncoterm $incoterm): array
    {
        return [
            'id' => $incoterm->id,
            'codi' => $incoterm->codi,
            'nom' => $incoterm->nom,
            'tracking_steps' => $incoterm->trackingSteps
                ->map(fn ($trackingStep) => [
                    'id' => $trackingStep->id,
                    'ordre' => $trackingStep->ordre,
                    'nom' => $trackingStep->nom,
                ])
                ->values(),
        ];
    }
}