<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Port extends Model
{
    use HasFactory;

    protected $table = 'ports';

    public $timestamps = false;

    protected $fillable = [
        'nom',
        'ciutat_id',
    ];

    public function ciutat()
    {
        return $this->belongsTo(Ciutat::class, 'ciutat_id');
    }

    public function ofertesOrigen()
    {
        return $this->hasMany(Oferta::class, 'port_origen_id');
    }

    public function ofertesDesti()
    {
        return $this->hasMany(Oferta::class, 'port_desti_id');
    }
}
