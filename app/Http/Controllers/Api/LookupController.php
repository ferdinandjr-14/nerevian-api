<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesApiRequests;
use App\Http\Controllers\Controller;
use App\Models\Aeroport;
use App\Models\Client;
use App\Models\EstatOferta;
use App\Models\Incoterm;
use App\Models\LiniaTransportMaritim;
use App\Models\Pais;
use App\Models\Port;
use App\Models\Rol;
use App\Models\TipusCarrega;
use App\Models\TipusContenidor;
use App\Models\TipusFluxe;
use App\Models\TipusTransport;
use App\Models\TipusValidacio;
use App\Models\TrackingStep;
use App\Models\Transportista;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LookupController extends Controller
{
    use AuthorizesApiRequests;

    public function __invoke(Request $request): JsonResponse
    {
        $user = $this->currentUser($request);

        return response()->json([
            'rols' => $this->hasRole($user, 'admin') ? Rol::query()->orderBy('id')->get() : [],
            'clients' => $this->hasRole($user, 'admin', 'operator', 'commercial') ? Client::query()->orderBy('nom')->get() : [],
            'estats_ofertes' => EstatOferta::query()->orderBy('id')->get(),
            'tipus_transports' => TipusTransport::query()->orderBy('id')->get(),
            'tipus_fluxes' => TipusFluxe::query()->orderBy('id')->get(),
            'tipus_carrega' => TipusCarrega::query()->orderBy('id')->get(),
            'tipus_contenidors' => TipusContenidor::query()->orderBy('id')->get(),
            'tipus_validacions' => TipusValidacio::query()->orderBy('id')->get(),
            'tracking_steps' => TrackingStep::query()->orderBy('ordre')->get(),
            'incoterms' => Incoterm::query()
                ->with(['tipusIncoterm', 'trackingStep'])
                ->orderBy('id')
                ->get(),
            'paissos' => Pais::query()->with('ciutats')->orderBy('nom')->get(),
            'aeroports' => Aeroport::query()->with('ciutat.pais')->orderBy('nom')->get(),
            'ports' => Port::query()->with('ciutat.pais')->orderBy('nom')->get(),
            'transportistes' => Transportista::query()->with('ciutat.pais')->orderBy('nom')->get(),
            'linies_transport_maritim' => LiniaTransportMaritim::query()->with('ciutat.pais')->orderBy('nom')->get(),
        ]);
    }
}
