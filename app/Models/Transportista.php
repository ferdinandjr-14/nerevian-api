<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transportista extends Model
{
    use HasFactory;

    protected $table = 'transportistes';

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
        return $this->hasMany(Oferta::class, 'transportista_id');
    }
}
