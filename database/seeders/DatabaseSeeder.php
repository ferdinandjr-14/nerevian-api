<?php

namespace Database\Seeders;

use App\Models\Rol;
use App\Models\Usuari;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $adminRole = Rol::firstOrCreate([
            'rol' => 'admin',
        ]);

        Usuari::firstOrCreate(
            ['correu' => 'admin@nerevian.test'],
            [
                'contrasenya' => 'password123',
                'nom' => 'Admin',
                'cognoms' => 'User',
                'rol_id' => $adminRole->id,
                'client_id' => null,
            ]
        );
    }
}
