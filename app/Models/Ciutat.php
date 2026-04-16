<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ciutat extends Model
{

    protected $table = 'ciutats';

    public $timestamps = false;

    protected $fillable = [
        'nom',
        'pais_id',
    ];

    public function pais()
    {
        return $this->belongsTo(Pais::class, 'pais_id');
    }

    public function aeroports()
    {
        return $this->hasMany(Aeroport::class, 'ciutat_id');
    }

    public function ports()
    {
        return $this->hasMany(Port::class, 'ciutat_id');
    }

    public function transportistes()
    {
        return $this->hasMany(Transportista::class, 'ciutat_id');
    }

    public function liniesTransportMaritim()
    {
        return $this->hasMany(LiniaTransportMaritim::class, 'ciutat_id');
    }
}
