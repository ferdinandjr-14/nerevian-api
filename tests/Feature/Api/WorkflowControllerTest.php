<?php

namespace Tests\Feature\Api;

use App\Models\Client;
use App\Models\EstatOferta;
use App\Models\Incoterm;
use App\Models\Oferta;
use App\Models\Rol;
use App\Models\TipusCarrega;
use App\Models\TipusFluxe;
use App\Models\TipusIncoterm;
use App\Models\TipusTransport;
use App\Models\TrackingStep;
use App\Models\Usuari;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WorkflowControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_client_user(): void
    {
        $admin = $this->createUser('admin');
        $client = Client::create([
            'nom' => 'Oceanic Cargo',
            'cif' => 'A11111111',
        ]);

        $response = $this->actingAsApi($admin)->postJson('/api/admin/users', [
            'nom' => 'Client',
            'cognoms' => 'User',
            'correu' => 'client@example.com',
            'contrasenya' => 'secret123',
            'contrasenya_confirmation' => 'secret123',
            'rol_id' => $this->roleId('client'),
            'client_id' => $client->id,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('user.client_id', $client->id)
            ->assertJsonPath('user.rol.rol', 'client');
    }

    public function test_operator_can_create_pending_offer_and_client_can_accept_it(): void
    {
        $this->seedStatuses();
        [$incoterm] = $this->seedOfferCatalog();
        $client = Client::create([
            'nom' => 'Import Export Co.',
            'cif' => 'B22222222',
        ]);

        $operator = $this->createUser('operator');
        $clientUser = $this->createUser('client', $client->id);

        $offerResponse = $this->actingAsApi($operator)->postJson('/api/offers', [
            'tipus_transport_id' => TipusTransport::firstOrFail()->id,
            'tipus_fluxe_id' => TipusFluxe::firstOrFail()->id,
            'tipus_carrega_id' => TipusCarrega::firstOrFail()->id,
            'incoterm_id' => $incoterm->id,
            'client_id' => $client->id,
            'comentaris' => 'Need a door to door export quote.',
        ]);

        $offerResponse
            ->assertCreated()
            ->assertJsonPath('offer.estat_oferta_id', $this->statusId('Pending'));

        $offerId = $offerResponse->json('offer.id');

        $this->actingAsApi($clientUser)->postJson("/api/offers/{$offerId}/decision", [
            'decision' => 'accept',
        ])
            ->assertOk()
            ->assertJsonPath('offer.estat_oferta_id', $this->statusId('Accepted'));
    }

    public function test_commercial_can_update_tracking_for_an_active_offer(): void
    {
        $this->seedStatuses();
        [$incoterm, $finalStep, $firstStep] = $this->seedOfferCatalog();
        $client = Client::create([
            'nom' => 'Blue Freight',
            'cif' => 'B33333333',
        ]);

        $commercial = $this->createUser('commercial');
        $offer = Oferta::create([
            'tipus_transport_id' => TipusTransport::firstOrFail()->id,
            'tipus_fluxe_id' => TipusFluxe::firstOrFail()->id,
            'tipus_carrega_id' => TipusCarrega::firstOrFail()->id,
            'incoterm_id' => $incoterm->id,
            'client_id' => $client->id,
            'estat_oferta_id' => $this->statusId('Accepted'),
            'data_creacio' => now()->toDateString(),
        ]);

        $this->actingAsApi($commercial)->postJson("/api/offers/{$offer->id}/tracking", [
            'tracking_step_id' => $firstStep->id,
            'observacions' => 'Cargo picked up.',
        ])
            ->assertOk()
            ->assertJsonPath('tracking.status', 'Shipped')
            ->assertJsonPath('tracking.steps.0.completed', true);

        $this->actingAsApi($commercial)->postJson("/api/offers/{$offer->id}/tracking", [
            'tracking_step_id' => $finalStep->id,
            'observacions' => 'Delivered.',
        ])
            ->assertOk()
            ->assertJsonPath('tracking.status', 'Finalized');
    }

    private function actingAsApi(Usuari $user): self
    {
        Sanctum::actingAs($user);

        return $this;
    }

    private function createUser(string $role, ?int $clientId = null): Usuari
    {
        return Usuari::create([
            'nom' => ucfirst($role),
            'cognoms' => 'Tester',
            'correu' => $role.($clientId ? $clientId : '').'@example.com',
            'contrasenya' => 'secret123',
            'rol_id' => $this->roleId($role),
            'client_id' => $clientId,
        ]);
    }

    private function roleId(string $role): int
    {
        return Rol::firstOrCreate(['rol' => $role])->id;
    }

    private function seedStatuses(): void
    {
        foreach (['Pending', 'Accepted', 'Rejected', 'Shipped', 'Delayed', 'Finalized'] as $status) {
            EstatOferta::firstOrCreate(['estat' => $status]);
        }
    }

    private function seedOfferCatalog(): array
    {
        TipusTransport::firstOrCreate(['tipus' => 'Maritime']);
        TipusFluxe::firstOrCreate(['tipus' => 'Export']);
        TipusCarrega::firstOrCreate(['tipus' => 'FCL']);

        $firstStep = TrackingStep::firstOrCreate(
            ['ordre' => 1],
            ['nom' => 'Pickup at origin']
        );

        $finalStep = TrackingStep::firstOrCreate(
            ['ordre' => 2],
            ['nom' => 'Delivered to final customer']
        );

        $tipusIncoterm = TipusIncoterm::firstOrCreate(
            ['codi' => 'DAP'],
            ['nom' => 'Delivered at Place']
        );

        $incoterm = Incoterm::create([
            'tipus_inconterm_id' => $tipusIncoterm->id,
            'tracking_steps_id' => $finalStep->id,
        ]);

        return [$incoterm, $finalStep, $firstStep];
    }

    private function statusId(string $status): int
    {
        return EstatOferta::query()->where('estat', $status)->valueOrFail('id');
    }
}
