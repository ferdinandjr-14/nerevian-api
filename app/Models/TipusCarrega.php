<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipusCarrega extends Model
{
    use HasFactory;

    protected $table = 'tipus_carrega';

    public $timestamps = false;

    protected $fillable = [
        'tipus',
    ];

    public function ofertes()
    {
        return $this->hasMany(Oferta::class, 'tipus_carrega_id');
    }
}
