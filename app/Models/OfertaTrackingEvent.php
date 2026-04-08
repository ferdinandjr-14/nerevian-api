<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfertaTrackingEvent extends Model
{
    use HasFactory;

    protected $table = 'oferta_tracking_events';

    protected $fillable = [
        'oferta_id',
        'tracking_step_id',
        'updated_by_id',
        'observacions',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    public function oferta()
    {
        return $this->belongsTo(Oferta::class, 'oferta_id');
    }

    public function trackingStep()
    {
        return $this->belongsTo(TrackingStep::class, 'tracking_step_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(Usuari::class, 'updated_by_id');
    }
}
