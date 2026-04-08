<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Oferta extends Model
{
    use HasFactory;

    protected $table = 'ofertes';

    public $timestamps = false;

    protected $fillable = [
        'tipus_transport_id',
        'tipus_fluxe_id',
        'tipus_carrega_id',
        'incoterm_id',
        'client_id',
        'comentaris',
        'agent_comercial_id',
        'transportista_id',
        'pes_brut',
        'volum',
        'tipus_validacio_id',
        'port_origen_id',
        'port_desti_id',
        'aeroport_origen_id',
        'aeroport_desti_id',
        'linia_transport_maritim_id',
        'estat_oferta_id',
        'operador_id',
        'data_creacio',
        'data_validessa_inicial',
        'data_validessa_fina',
        'rao_rebuig',
        'tipus_contenidor_id',
    ];

    protected function casts(): array
    {
        return [
            'data_creacio' => 'date',
            'data_validessa_inicial' => 'date',
            'data_validessa_fina' => 'date',
            'pes_brut' => 'decimal:3',
            'volum' => 'decimal:3',
        ];
    }

    public function tipusTransport()
    {
        return $this->belongsTo(TipusTransport::class, 'tipus_transport_id');
    }

    public function tipusFluxe()
    {
        return $this->belongsTo(TipusFluxe::class, 'tipus_fluxe_id');
    }

    public function tipusCarrega()
    {
        return $this->belongsTo(TipusCarrega::class, 'tipus_carrega_id');
    }

    public function tipusContenidor()
    {
        return $this->belongsTo(TipusContenidor::class, 'tipus_contenidor_id');
    }

    public function tipusValidacio()
    {
        return $this->belongsTo(TipusValidacio::class, 'tipus_validacio_id');
    }

    public function estatOferta()
    {
        return $this->belongsTo(EstatOferta::class, 'estat_oferta_id');
    }

    public function incoterm()
    {
        return $this->belongsTo(Incoterm::class, 'incoterm_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function operador()
    {
        return $this->belongsTo(Usuari::class, 'operador_id');
    }

    public function agentComercial()
    {
        return $this->belongsTo(Usuari::class, 'agent_comercial_id');
    }

    public function transportista()
    {
        return $this->belongsTo(Transportista::class, 'transportista_id');
    }

    public function portOrigen()
    {
        return $this->belongsTo(Port::class, 'port_origen_id');
    }

    public function portDesti()
    {
        return $this->belongsTo(Port::class, 'port_desti_id');
    }

    public function aeroportOrigen()
    {
        return $this->belongsTo(Aeroport::class, 'aeroport_origen_id');
    }

    public function aeroportDesti()
    {
        return $this->belongsTo(Aeroport::class, 'aeroport_desti_id');
    }

    public function liniaTransportMaritim()
    {
        return $this->belongsTo(LiniaTransportMaritim::class, 'linia_transport_maritim_id');
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'oferta_id');
    }

    public function trackingEvents()
    {
        return $this->hasMany(OfertaTrackingEvent::class, 'oferta_id');
    }
}
