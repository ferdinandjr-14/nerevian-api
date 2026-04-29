<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pais extends Model
{

    protected $table = 'paissos';

    public $timestamps = false;

    protected $fillable = [
        'nom',
    ];

    public function ciutats()
    {
        return $this->hasMany(Ciutat::class, 'pais_id');
    }
}
