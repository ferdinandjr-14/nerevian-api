<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesApiRequests;
use App\Http\Controllers\Controller;
use App\Models\EstatOferta;
use App\Models\Oferta;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OfferController extends Controller
{
    use AuthorizesApiRequests;

    private array $relations = [
        'tipusTransport',
        'tipusFluxe',
        'tipusCarrega',
        'tipusContenidor',
        'tipusValidacio',
        'estatOferta',
        'incoterm.tipusIncoterm',
        'incoterm.trackingStep',
        'client',
        'operador.rol',
        'operador.client',
        'agentComercial.rol',
        'transportista.ciutat.pais',
        'portOrigen.ciutat.pais',
        'portDesti.ciutat.pais',
        'aeroportOrigen.ciutat.pais',
        'aeroportDesti.ciutat.pais',
        'liniaTransportMaritim.ciutat.pais',
        'documents.uploadedBy',
        'trackingEvents.trackingStep',
        'trackingEvents.updatedBy',
    ];

    public function index(Request $request): JsonResponse
    {
        $user = $this->currentUser($request);

        $query = Oferta::query()
            ->with($this->relations)
            ->latest('id');

        if ($this->hasRole($user, 'client')) {
            $query->where('client_id', $user->client_id);
        }

        $scope = $request->string('scope')->toString();
        $status = $request->string('status')->toString();

        if ($scope !== '') {
            $this->applyScopeFilter($query, $scope);
        }

        if ($status !== '') {
            $query->whereHas('estatOferta', function (Builder $builder) use ($status): void {
                $builder->where('estat', $status);
            });
        }

        return response()->json($query->paginate((int) $request->integer('per_page', 15)));
    }

    public function store(Request $request): JsonResponse
    {
        $user = $this->requireRoles($request, ['operator', 'admin']);

        $validated = $request->validate($this->offerRules());
        $validated['estat_oferta_id'] = $this->statusId('Pending');
        $validated['operador_id'] = $validated['operador_id'] ?? $user->id;
        $validated['data_creacio'] = $validated['data_creacio'] ?? now()->toDateString();
        $validated['rao_rebuig'] = null;

        $oferta = Oferta::create($validated);

        return response()->json([
            'message' => 'Oferta creada correctament.',
            'offer' => $oferta->load($this->relations),
        ], 201);
    }

    public function show(Request $request, Oferta $oferta): JsonResponse
    {
        $user = $this->currentUser($request);
        $this->authorizeOfferAccess($user, $oferta);

        return response()->json([
            'offer' => $oferta->load($this->relations),
        ]);
    }

    public function update(Request $request, Oferta $oferta): JsonResponse
    {
        $this->requireRoles($request, ['operator', 'admin']);

        abort_if(
            $oferta->loadMissing('estatOferta')->estatOferta?->estat === 'Finalized',
            409,
            'Finalized offers cannot be edited.'
        );

        $validated = $request->validate($this->offerRules(isUpdate: true));
        unset($validated['estat_oferta_id'], $validated['operador_id'], $validated['rao_rebuig']);

        $oferta->update($validated);

        return response()->json([
            'message' => 'Oferta actualitzada correctament.',
            'offer' => $oferta->fresh()->load($this->relations),
        ]);
    }

    public function respond(Request $request, Oferta $oferta): JsonResponse
    {
        $user = $this->requireRoles($request, ['client']);
        $this->authorizeOfferAccess($user, $oferta);

        abort_if(
            $oferta->loadMissing('estatOferta')->estatOferta?->estat !== 'Pending',
            409,
            'Only pending offers can be answered.'
        );

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
            'message' => $accepted ? 'Oferta acceptada correctament.' : 'Oferta rebutjada correctament.',
            'offer' => $oferta->fresh()->load($this->relations),
        ]);
    }

    private function offerRules(bool $isUpdate = false): array
    {
        $required = $isUpdate ? ['sometimes'] : ['required'];

        return [
            'tipus_transport_id' => array_merge($required, ['integer', 'exists:tipus_transports,id']),
            'tipus_fluxe_id' => array_merge($required, ['integer', 'exists:tipus_fluxes,id']),
            'tipus_carrega_id' => array_merge($required, ['integer', 'exists:tipus_carrega,id']),
            'incoterm_id' => array_merge($required, ['integer', 'exists:incoterms,id']),
            'client_id' => array_merge($required, ['integer', 'exists:clients,id']),
            'comentaris' => ['nullable', 'string'],
            'agent_comercial_id' => ['nullable', 'integer', 'exists:usuaris,id'],
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

    private function applyScopeFilter(Builder $query, string $scope): void
    {
        match ($scope) {
            'pending' => $query->where('estat_oferta_id', $this->statusId('Pending')),
            'active' => $query->whereIn('estat_oferta_id', [
                $this->statusId('Accepted'),
                $this->statusId('Shipped'),
                $this->statusId('Delayed'),
            ]),
            'finalized' => $query->where('estat_oferta_id', $this->statusId('Finalized')),
            default => null,
        };
    }

    private function statusId(string $status): int
    {
        return EstatOferta::query()
            ->where('estat', $status)
            ->valueOrFail('id');
    }
}
