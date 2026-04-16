<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstatOferta extends Model
{

    protected $table = 'estats_ofertes';

    public $timestamps = false;

    protected $fillable = [
        'estat',
    ];

    public function ofertes()
    {
        return $this->hasMany(Oferta::class, 'estat_oferta_id');
    }
}
