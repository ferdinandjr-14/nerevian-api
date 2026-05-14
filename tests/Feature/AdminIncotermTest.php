<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\TipusIncoterm;
use App\Models\TrackingStep;
use App\Models\Usuari;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminIncotermTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_and_delete_incoterm_with_steps(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);

        $firstStep = TrackingStep::create(['ordre' => 1, 'nom' => 'Step 1']);
        $secondStep = TrackingStep::create(['ordre' => 2, 'nom' => 'Step 2']);
        $thirdStep = TrackingStep::create(['ordre' => 3, 'nom' => 'Step 3']);

        $createResponse = $this->postJson('/api/admin/incoterms', [
            'codi' => 'EXW',
            'nom' => 'Ex Works',
            'tracking_step_ids' => [$firstStep->id, $secondStep->id],
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('incoterm.codi', 'EXW')
            ->assertJsonCount(2, 'incoterm.tracking_steps');

        $incotermId = $createResponse->json('incoterm.id');

        $this->assertDatabaseHas('tipus_incoterms', [
            'id' => $incotermId,
            'codi' => 'EXW',
            'nom' => 'Ex Works',
        ]);

        $this->assertDatabaseHas('incoterms', [
            'tipus_inconterm_id' => $incotermId,
            'tracking_steps_id' => $firstStep->id,
        ]);

        $updateResponse = $this->putJson('/api/admin/incoterms/' . $incotermId, [
            'codi' => 'EXW',
            'nom' => 'Ex Works Updated',
            'tracking_step_ids' => [$secondStep->id, $thirdStep->id],
        ]);

        $updateResponse
            ->assertOk()
            ->assertJsonPath('incoterm.nom', 'Ex Works Updated')
            ->assertJsonCount(2, 'incoterm.tracking_steps');

        $this->assertDatabaseHas('tipus_incoterms', [
            'id' => $incotermId,
            'nom' => 'Ex Works Updated',
        ]);

        $this->assertDatabaseMissing('incoterms', [
            'tipus_inconterm_id' => $incotermId,
            'tracking_steps_id' => $firstStep->id,
        ]);

        $deleteResponse = $this->deleteJson('/api/admin/incoterms/' . $incotermId);

        $deleteResponse->assertOk();

        $this->assertDatabaseMissing('tipus_incoterms', [
            'id' => $incotermId,
        ]);
    }

    private function createAdmin(): Usuari
    {
        $adminRole = Rol::create(['rol' => 'admin']);

        return Usuari::create([
            'correu' => 'admin@example.com',
            'contrasenya' => 'password123',
            'nom' => 'Admin',
            'cognoms' => 'User',
            'rol_id' => $adminRole->id,
            'client_id' => null,
        ]);
    }
}