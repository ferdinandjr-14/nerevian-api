<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{

    protected $table = 'rols';

    public $timestamps = false;

    protected $fillable = [
        'rol',
    ];

    public function usuaris()
    {
        return $this->hasMany(Usuari::class, 'rol_id');
    }
}
