<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\AuthorizesApiRequests;
use App\Http\Controllers\Controller;
use App\Models\EstatOferta;
use App\Models\Oferta;
use App\Models\TrackingStep;
use App\Services\SupabaseDocumentStorage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class OfferController extends Controller
{
    use AuthorizesApiRequests;

    private SupabaseDocumentStorage $documentStorage;

    private array $relations = [
        'tipusTransport',
        'tipusFluxe',
        'tipusCarrega',
        'tipusContenidor',
        'tipusValidacio',
        'estatOferta',
        'incoterm.tipusIncoterm',
        'incoterm.trackingStep',
        'trackingStep',
        'client',
        'operador.rol',
        'agentComercial.rol',
        'transportista.ciutat.pais',
        'portOrigen.ciutat.pais',
        'portDesti.ciutat.pais',
        'aeroportOrigen.ciutat.pais',
        'aeroportDesti.ciutat.pais',
        'liniaTransportMaritim.ciutat.pais',
    ];

    public function __construct(SupabaseDocumentStorage $documentStorage)
    {
        $this->documentStorage = $documentStorage;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $user = $this->currentUser($request);

            $query = Oferta::query()
                ->with($this->relations)
                ->latest('id');

            $this->applyOfferVisibility($query, $user);

            return response()->json($query->get());
        } catch (Throwable $exception) {
            if ($exception instanceof ApiException) {
                throw $exception;
            }

            throw ApiException::serverError();
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $user = $this->requireRoles($request, ['operator', 'admin']);

            $validated = $request->validate($this->storeRules());
            $validated['estat_oferta_id'] = $this->statusId('Pending');
            $validated['operador_id'] = $validated['operador_id'] ?? $user->id;
            $validated['data_creacio'] = $validated['data_creacio'] ?? now()->toDateString();
            $validated['rao_rebuig'] = null;

            $oferta = Oferta::create($validated);

            return response()->json([
                'message' => 'Offer created',
                'offer' => $oferta->load($this->relations),
            ], 201);
        } catch (Throwable $exception) {
            if ($exception instanceof ApiException || $exception instanceof ValidationException) {
                throw $exception;
            }

            throw ApiException::serverError();
        }
    }

    public function show(Request $request, Oferta $oferta): JsonResponse
    {
        try {
            $user = $this->currentUser($request);
            $this->authorizeOfferAccess($user, $oferta);

            return response()->json([
                'offer' => $oferta->load($this->relations),
            ]);
        } catch (Throwable $exception) {
            if ($exception instanceof ApiException) {
                throw $exception;
            }

            throw ApiException::serverError();
        }
    }

    public function update(Request $request, Oferta $oferta): JsonResponse
    {
        try {
            $this->requireRoles($request, ['operator', 'admin']);

            if ($this->offerStatus($oferta) === 'Finalized') {
                throw ApiException::make('Finalized offers cannot be edited.', 409);
            }

            $validated = $request->validate($this->updateRules());
            unset($validated['estat_oferta_id'], $validated['operador_id'], $validated['rao_rebuig']);

            $oferta->update($validated);

            return response()->json([
                'message' => 'Offer updated successfully',
                'offer' => $oferta->fresh()->load($this->relations),
            ]);
        } catch (Throwable $exception) {
            if ($exception instanceof ApiException || $exception instanceof ValidationException) {
                throw $exception;
            }

            throw ApiException::serverError();
        }
    }

    public function respond(Request $request, Oferta $oferta): JsonResponse
    {
        try {
            $user = $this->requireRoles($request, ['client']);
            $this->authorizeOfferAccess($user, $oferta);

            if ($this->offerStatus($oferta) !== 'Pending') {
                throw ApiException::make('Only pending offers can be answered.', 409);
            }

            $validated = $request->validate([
                'decision' => ['required', Rule::in(['accept', 'reject'])],
                'rao_rebuig' => ['nullable', 'string', 'required_if:decision,reject'],
            ]);

            $accepted = $validated['decision'] === 'accept';

            $oferta->update([
                'estat_oferta_id' => $this->statusId($accepted ? 'Accepted' : 'Rejected'),
                'rao_rebuig' => $accepted ? null : $validated['rao_rebuig'],
            ]);

            return response()->json([
                'message' => $accepted ? 'Offer accepted successfully' : 'Offer rejected successfully',
                'offer' => $oferta->fresh()->load($this->relations),
            ]);
        } catch (Throwable $exception) {
            if ($exception instanceof ApiException || $exception instanceof ValidationException) {
                throw $exception;
            }

            throw ApiException::serverError();
        }
    }

    public function uploadDocuments(Request $request, Oferta $oferta): JsonResponse
    {
        try {
            $user = $this->requireRoles($request, ['operator', 'admin']);
            $this->authorizeOfferAccess($user, $oferta);

            $validated = $request->validate([
                'documents' => ['required', 'array', 'min:1'],
                'documents.*' => ['required', 'file', 'max:10240'],
            ]);

            $this->documentStorage->uploadOfferDocuments($oferta, $validated['documents']);

            return response()->json([
                'message' => 'Documents uploaded successfully',
                'documents' => $this->documentStorage->getOfferDocuments($oferta),
            ]);
        } catch (Throwable $exception) {
            if ($exception instanceof ApiException || $exception instanceof ValidationException) {
                throw $exception;
            }

            throw ApiException::serverError();
        }
    }

    public function documents(Request $request, Oferta $oferta): JsonResponse
    {
        try {
            $user = $this->currentUser($request);
            $this->authorizeOfferAccess($user, $oferta);

            return response()->json([
                'documents' => $this->documentStorage->getOfferDocuments($oferta),
            ]);
        } catch (Throwable $exception) {
            if ($exception instanceof ApiException) {
                throw $exception;
            }

            throw ApiException::serverError();
        }
    }

    public function trackingOptions(Request $request, Oferta $oferta): JsonResponse
    {
        try {
            $user = $this->currentUser($request);
            $this->authorizeOfferAccess($user, $oferta);

            return response()->json([
                'tracking_steps' => $this->availableTrackingSteps($oferta),
            ]);
        } catch (Throwable $exception) {
            if ($exception instanceof ApiException) {
                throw $exception;
            }

            throw ApiException::serverError();
        }
    }

    public function trackingStep(Request $request, Oferta $oferta): JsonResponse
    {
        try {
            $user = $this->currentUser($request);
            $this->authorizeOfferAccess($user, $oferta);

            $oferta->loadMissing('trackingStep');

            return response()->json([
                'tracking_step' => $oferta->trackingStep,
            ]);
        } catch (Throwable $exception) {
            if ($exception instanceof ApiException) {
                throw $exception;
            }

            throw ApiException::serverError();
        }
    }

    public function updateTrackingStep(Request $request, Oferta $oferta): JsonResponse
    {
        try {
            $user = $this->requireRoles($request, ['commercial', 'operator', 'admin']);
            $this->authorizeOfferAccess($user, $oferta);

            $availableTrackingStepIds = $this->availableTrackingSteps($oferta)
                ->pluck('id')
                ->all();

            if (empty($availableTrackingStepIds)) {
                throw ApiException::make('This offer does not have tracking steps available.', 422);
            }

            $validated = $request->validate([
                'tracking_step_id' => ['required', 'integer', Rule::in($availableTrackingStepIds)],
            ]);

            $oferta->update([
                'tracking_step_id' => $validated['tracking_step_id'],
            ]);

            return response()->json([
                'message' => 'Offer tracking step updated successfully',
                'tracking_step' => $oferta->fresh()->load('trackingStep')->trackingStep,
            ]);
        } catch (Throwable $exception) {
            if ($exception instanceof ApiException || $exception instanceof ValidationException) {
                throw $exception;
            }

            throw ApiException::serverError();
        }
    }

    private function storeRules(): array
    {
        return [
            'tipus_transport_id' => ['required', 'integer', 'exists:tipus_transports,id'],
            'tipus_fluxe_id' => ['required', 'integer', 'exists:tipus_fluxes,id'],
            'tipus_carrega_id' => ['required', 'integer', 'exists:tipus_carrega,id'],
            'incoterm_id' => ['required', 'integer', 'exists:incoterms,id'],
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'comentaris' => ['nullable', 'string'],
            'agent_comercial_id' => ['nullable', 'integer', 'exists:usuaris,id'],
            'preu' => ['nullable', 'numeric', 'min:0'],
            'transportista_id' => ['nullable', 'integer', 'exists:transportistes,id'],
            'pes_brut' => ['nullable', 'numeric', 'min:0'],
            'volum' => ['nullable', 'numeric', 'min:0'],
            'tipus_validacio_id' => ['nullable', 'integer', 'exists:tipus_validacions,id'],
            'port_origen_id' => ['nullable', 'integer', 'exists:ports,id'],
            'port_desti_id' => ['nullable', 'integer', 'exists:ports,id'],
            'aeroport_origen_id' => ['nullable', 'integer', 'exists:aeroports,id'],
            'aeroport_desti_id' => ['nullable', 'integer', 'exists:aeroports,id'],
            'linia_transport_maritim_id' => ['nullable', 'integer', 'exists:linies_transport_maritim,id'],
            'data_creacio' => ['nullable', 'date'],
            'data_validessa_inicial' => ['nullable', 'date'],
            'data_validessa_fina' => ['nullable', 'date', 'after_or_equal:data_validessa_inicial'],
            'tipus_contenidor_id' => ['nullable', 'integer', 'exists:tipus_contenidors,id'],
            'operador_id' => ['nullable', 'integer', 'exists:usuaris,id'],
        ];
    }

    private function updateRules(): array
    {
        return [
            'tipus_transport_id' => ['sometimes', 'integer', 'exists:tipus_transports,id'],
            'tipus_fluxe_id' => ['sometimes', 'integer', 'exists:tipus_fluxes,id'],
            'tipus_carrega_id' => ['sometimes', 'integer', 'exists:tipus_carrega,id'],
            'incoterm_id' => ['sometimes', 'integer', 'exists:incoterms,id'],
            'client_id' => ['sometimes', 'integer', 'exists:clients,id'],
            'comentaris' => ['nullable', 'string'],
            'agent_comercial_id' => ['nullable', 'integer', 'exists:usuaris,id'],
            'preu' => ['nullable', 'numeric', 'min:0'],
            'transportista_id' => ['nullable', 'integer', 'exists:transportistes,id'],
            'pes_brut' => ['nullable', 'numeric', 'min:0'],
            'volum' => ['nullable', 'numeric', 'min:0'],
            'tipus_validacio_id' => ['nullable', 'integer', 'exists:tipus_validacions,id'],
            'port_origen_id' => ['nullable', 'integer', 'exists:ports,id'],
            'port_desti_id' => ['nullable', 'integer', 'exists:ports,id'],
            'aeroport_origen_id' => ['nullable', 'integer', 'exists:aeroports,id'],
            'aeroport_desti_id' => ['nullable', 'integer', 'exists:aeroports,id'],
            'linia_transport_maritim_id' => ['nullable', 'integer', 'exists:linies_transport_maritim,id'],
            'data_creacio' => ['nullable', 'date'],
            'data_validessa_inicial' => ['nullable', 'date'],
            'data_validessa_fina' => ['nullable', 'date', 'after_or_equal:data_validessa_inicial'],
            'tipus_contenidor_id' => ['nullable', 'integer', 'exists:tipus_contenidors,id'],
            'operador_id' => ['nullable', 'integer', 'exists:usuaris,id'],
        ];
    }

    private function statusId(string $status): int
    {
        $estatOferta = EstatOferta::query()
            ->where('estat', $status)
            ->first();

        if ($estatOferta === null) {
            throw ApiException::make("Offer status {$status} was not found.", 500);
        }

        return (int) $estatOferta->id;
    }

    private function availableTrackingSteps(Oferta $oferta): Collection
    {
        $oferta->loadMissing('incoterm');
        $incoterm = $oferta->incoterm;

        if ($incoterm === null) {
            return collect();
        }

        $tipusIncotermId = $incoterm->tipus_inconterm_id;

        if ($tipusIncotermId === null) {
            return collect();
        }

        return TrackingStep::query()
            ->whereHas('incoterms', function (Builder $builder) use ($tipusIncotermId): void {
                $builder->where('tipus_inconterm_id', $tipusIncotermId);
            })
            ->orderBy('ordre')
            ->get();
    }

    private function offerStatus(Oferta $oferta): ?string
    {
        $oferta->loadMissing('estatOferta');

        if ($oferta->estatOferta === null) {
            return null;
        }

        return $oferta->estatOferta->estat;
    }
}
