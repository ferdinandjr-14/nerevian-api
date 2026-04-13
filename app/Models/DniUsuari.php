<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DniUsuari extends Model
{
    use HasFactory;

    protected $table = 'dni_usuaris';

    public $timestamps = false;

    protected $fillable = [
        'usuari_id',
        'path',
    ];

    public function usuari()
    {
        return $this->belongsTo(Usuari::class, 'usuari_id');
    }
}
