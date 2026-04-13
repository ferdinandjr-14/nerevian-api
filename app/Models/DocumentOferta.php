<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentOferta extends Model
{
    use HasFactory;

    protected $table = 'documents_ofertes';

    public $timestamps = false;

    protected $fillable = [
        'oferta_id',
        'path',
    ];

    public function oferta()
    {
        return $this->belongsTo(Oferta::class, 'oferta_id');
    }
}
