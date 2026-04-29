<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Aeroport extends Model
{

    protected $table = 'aeroports';

    public $timestamps = false;

    protected $fillable = [
        'codi',
        'nom',
        'ciutat_id',
    ];

    public function ciutat()
    {
        return $this->belongsTo(Ciutat::class, 'ciutat_id');
    }

    public function ofertesOrigen()
    {
        return $this->hasMany(Oferta::class, 'aeroport_origen_id');
    }

    public function ofertesDesti()
    {
        return $this->hasMany(Oferta::class, 'aeroport_desti_id');
    }
}
