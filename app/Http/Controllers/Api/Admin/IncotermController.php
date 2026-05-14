<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Concerns\AuthorizesApiRequests;
use App\Http\Controllers\Controller;
use App\Models\TipusIncoterm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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

        $incoterm = DB::transaction(function () use ($validated): TipusIncoterm {
            $incoterm = TipusIncoterm::create([
                'codi' => $validated['codi'],
                'nom' => $validated['nom'],
            ]);

            $incoterm->trackingSteps()->sync($validated['tracking_step_ids']);

            return $incoterm->load('trackingSteps');
        });

        return response()->json([
            'message' => 'Incoterm created successfully.',
            'incoterm' => $this->formatIncoterm($incoterm),
        ], 201);
    }

    public function update(Request $request, TipusIncoterm $incoterm): JsonResponse
    {
        $this->requireRoles($request, ['admin']);

        $validated = $request->validate($this->rules($incoterm));

        $incoterm = DB::transaction(function () use ($incoterm, $validated): TipusIncoterm {
            $incoterm->update([
                'codi' => $validated['codi'],
                'nom' => $validated['nom'],
            ]);

            $incoterm->trackingSteps()->sync($validated['tracking_step_ids']);

            return $incoterm->load('trackingSteps');
        });

        return response()->json([
            'message' => 'Incoterm updated successfully.',
            'incoterm' => $this->formatIncoterm($incoterm),
        ]);
    }

    public function destroy(Request $request, TipusIncoterm $incoterm): JsonResponse
    {
        $this->requireRoles($request, ['admin']);

        $incoterm->delete();

        return response()->json([
            'message' => 'Incoterm deleted successfully.',
        ]);
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