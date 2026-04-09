<?php

namespace Tests\Feature\Api;

use App\Models\Client;
use App\Models\Rol;
use App\Models\Usuari;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_correu_and_contrasenya(): void
    {
        $rol = Rol::create([
            'rol' => 'client',
        ]);

        $client = Client::create([
            'nom' => 'Acme Logistics',
            'cif' => 'B12345678',
        ]);

        $usuari = Usuari::create([
            'nom' => 'Grace',
            'cognoms' => 'Hopper',
            'correu' => 'grace@example.com',
            'contrasenya' => 'secret123',
            'rol_id' => $rol->id,
            'client_id' => $client->id,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'correu' => $usuari->correu,
            'contrasenya' => 'secret123',
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'message',
                'token',
                'token_type',
                'user' => ['id', 'nom', 'cognoms', 'correu', 'rol_id', 'client_id'],
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_authenticated_user_can_fetch_own_profile(): void
    {
        $rol = Rol::create([
            'rol' => 'operator',
        ]);

        $usuari = Usuari::create([
            'nom' => 'Linus',
            'cognoms' => 'Torvalds',
            'correu' => 'linus@example.com',
            'contrasenya' => 'secret123',
            'rol_id' => $rol->id,
        ]);

        Sanctum::actingAs($usuari);

        $this
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('user.correu', 'linus@example.com')
            ->assertJsonPath('user.rol.rol', 'operator');
    }

    public function test_authenticated_user_can_logout_and_revoke_current_token(): void
    {
        $rol = Rol::create([
            'rol' => 'admin',
        ]);

        $usuari = Usuari::create([
            'nom' => 'Margaret',
            'cognoms' => 'Hamilton',
            'correu' => 'margaret@example.com',
            'contrasenya' => 'secret123',
            'rol_id' => $rol->id,
        ]);

        Sanctum::actingAs($usuari);

        $this
            ->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Sessio tancada correctament.');
    }
}
