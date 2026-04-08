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

    protected $fillable = [
        'correu',
        'contrasenya',
        'nom',
        'cognoms',
        'rol_id',
        'client_id',
        'dni_document_path',
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

    public function documents()
    {
        return $this->hasMany(Document::class, 'usuari_id');
    }

    public function uploadedDocuments()
    {
        return $this->hasMany(Document::class, 'uploaded_by_id');
    }
}
