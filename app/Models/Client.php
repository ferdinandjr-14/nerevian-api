<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $table = 'clients';

    public $timestamps = false;

    protected $fillable = [
        'nom',
        'cif',
    ];

    public function ofertes()
    {
        return $this->hasMany(Oferta::class, 'client_id');
    }
}
