<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $table = 'documents_ofertes';

    protected $fillable = [
        'oferta_id',
        'usuari_id',
        'uploaded_by_id',
        'tipus',
        'nom_original',
        'disk',
        'path',
        'mime_type',
        'mida',
    ];

    public function oferta()
    {
        return $this->belongsTo(Oferta::class, 'oferta_id');
    }

    public function usuari()
    {
        return $this->belongsTo(Usuari::class, 'usuari_id');
    }

    public function uploadedBy()
    {
        return $this->belongsTo(Usuari::class, 'uploaded_by_id');
    }
}
