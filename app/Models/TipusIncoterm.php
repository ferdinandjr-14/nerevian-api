<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipusIncoterm extends Model
{

    protected $table = 'tipus_incoterms';

    public $timestamps = false;

    protected $fillable = [
        'codi',
        'nom',
    ];

    public function incoterms()
    {
        return $this->hasMany(Incoterm::class, 'tipus_inconterm_id');
    }

    public function trackingSteps()
    {
        return $this->belongsToMany(TrackingStep::class, 'incoterms', 'tipus_inconterm_id', 'tracking_steps_id')
            ->orderBy('ordre');
    }
}
