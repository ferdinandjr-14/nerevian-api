<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipusValidacio extends Model
{
    use HasFactory;

    protected $table = 'tipus_validacions';

    public $timestamps = false;

    protected $fillable = [
        'tipus',
    ];

    public function ofertes()
    {
        return $this->hasMany(Oferta::class, 'tipus_validacio_id');
    }
}
