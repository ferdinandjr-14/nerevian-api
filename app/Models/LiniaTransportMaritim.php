<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LiniaTransportMaritim extends Model
{

    protected $table = 'linies_transport_maritim';

    public $timestamps = false;

    protected $fillable = [
        'nom',
        'ciutat_id',
    ];

    public function ciutat()
    {
        return $this->belongsTo(Ciutat::class, 'ciutat_id');
    }

    public function ofertes()
    {
        return $this->hasMany(Oferta::class, 'linia_transport_maritim_id');
    }
}
