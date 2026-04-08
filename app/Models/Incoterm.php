<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Incoterm extends Model
{
    use HasFactory;

    protected $table = 'incoterms';

    public $timestamps = false;

    protected $fillable = [
        'tipus_inconterm_id',
        'tracking_steps_id',
    ];

    public function tipusIncoterm()
    {
        return $this->belongsTo(TipusIncoterm::class, 'tipus_inconterm_id');
    }

    public function trackingStep()
    {
        return $this->belongsTo(TrackingStep::class, 'tracking_steps_id');
    }

    public function ofertes()
    {
        return $this->hasMany(Oferta::class, 'incoterm_id');
    }
}
