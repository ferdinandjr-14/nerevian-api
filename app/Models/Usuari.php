<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Usuari extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'usuaris';
    public $timestamps = false;

    protected $fillable = [
        'correu',
        'contrasenya',
        'nom',
        'cognoms',
        'rol_id',
        'client_id',
    ];

    protected $hidden = [
        'contrasenya',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'contrasenya' => 'hashed',
        ];
    }

    public function getAuthPassword(): string
    {
        return $this->contrasenya;
    }

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function ofertes()
    {
        return $this->hasMany(Oferta::class, 'operador_id');
    }

    public function ofertesComAgentComercial()
    {
        return $this->hasMany(Oferta::class, 'agent_comercial_id');
    }
}
