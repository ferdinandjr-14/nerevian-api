<?php

namespace Tests\Feature\Api;

use App\Models\Rol;
use App\Models\Usuari;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_receive_a_token(): void
    {
        $rol = Rol::create([
            'rol' => 'admin',
        ]);

        $response = $this->postJson('/api/auth/register', [
            'nom' => 'Ada',
            'cognoms' => 'Lovelace',
            'correu' => 'ada@example.com',
            'contrasenya' => 'secret123',
            'contrasenya_confirmation' => 'secret123',
            'rol_id' => $rol->id,
        ]);

        $response
            ->assertCreated()
            ->assertJsonStructure([
                'message',
                'token',
                'token_type',
                'user' => ['id', 'nom', 'cognoms', 'correu', 'rol_id'],
            ]);

        $this->assertDatabaseHas('usuaris', [
            'correu' => 'ada@example.com',
            'nom' => 'Ada',
            'rol_id' => $rol->id,
        ]);

        $usuari = Usuari::where('correu', 'ada@example.com')->first();

        $this->assertNotNull($usuari);
        $this->assertTrue(Hash::check('secret123', $usuari->contrasenya));
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_user_can_login_with_correu_and_contrasenya(): void
    {
        $usuari = Usuari::create([
            'nom' => 'Grace',
            'cognoms' => 'Hopper',
            'correu' => 'grace@example.com',
            'contrasenya' => 'secret123',
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
                'user' => ['id', 'nom', 'cognoms', 'correu', 'rol_id'],
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_authenticated_user_can_fetch_own_profile(): void
    {
        $usuari = Usuari::create([
            'nom' => 'Linus',
            'cognoms' => 'Torvalds',
            'correu' => 'linus@example.com',
            'contrasenya' => 'secret123',
        ]);

        $token = $usuari->createToken('test-token')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('user.correu', 'linus@example.com');
    }

    public function test_authenticated_user_can_logout_and_revoke_current_token(): void
    {
        $usuari = Usuari::create([
            'nom' => 'Margaret',
            'cognoms' => 'Hamilton',
            'correu' => 'margaret@example.com',
            'contrasenya' => 'secret123',
        ]);

        $token = $usuari->createToken('logout-token')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Sessio tancada correctament.');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
