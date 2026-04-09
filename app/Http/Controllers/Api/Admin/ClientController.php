<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Concerns\AuthorizesApiRequests;
use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    use AuthorizesApiRequests;

    public function index(Request $request): JsonResponse
    {
        $this->requireRoles($request, ['admin']);

        return response()->json(
            Client::query()
                ->withCount(['ofertes', 'usuaris'])
                ->orderBy('nom')
                ->paginate((int) $request->integer('per_page', 15))
        );
    }

    public function show(Request $request, Client $client): JsonResponse
    {
        $this->requireRoles($request, ['admin']);

        return response()->json([
            'client' => $client->load(['usuaris.rol', 'ofertes.estatOferta']),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->requireRoles($request, ['admin']);

        $validated = $request->validate($this->rules());
        $client = Client::create($validated);

        return response()->json([
            'message' => 'Client creat correctament.',
            'client' => $client,
        ], 201);
    }

    public function update(Request $request, Client $client): JsonResponse
    {
        $this->requireRoles($request, ['admin']);

        $validated = $request->validate($this->rules($client));
        $client->update($validated);

        return response()->json([
            'message' => 'Client actualitzat correctament.',
            'client' => $client->fresh(),
        ]);
    }

    public function destroy(Request $request, Client $client): JsonResponse
    {
        $this->requireRoles($request, ['admin']);

        $client->delete();

        return response()->json([
            'message' => 'Client eliminat correctament.',
        ]);
    }

    private function rules(?Client $client = null): array
    {
        return [
            'nom' => [$client ? 'sometimes' : 'required', 'string', 'max:255'],
            'cif' => [
                $client ? 'sometimes' : 'required',
                'string',
                'max:255',
                Rule::unique('clients', 'cif')->ignore($client?->id),
            ],
        ];
    }
}
