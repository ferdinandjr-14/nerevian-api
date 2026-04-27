<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\AuthorizesApiRequests;
use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class ClientController extends Controller
{
    use AuthorizesApiRequests;

    public function index(Request $request): JsonResponse
    {
        try {
            $this->requireRoles($request, ['admin']);

            return response()->json(
                Client::query()
                    ->withCount(['ofertes', 'usuaris'])
                    ->orderBy('nom')
                    ->get()
            );
        } catch (Throwable $exception) {
            if ($exception instanceof ApiException) {
                throw $exception;
            }

            throw ApiException::serverError();
        }
    }

    public function show(Request $request, Client $client): JsonResponse
    {
        try {
            $this->requireRoles($request, ['admin', 'operator']);

            return response()->json([
                'client' => $client->load(['usuaris.rol', 'ofertes.estatOferta']),
            ]);
        } catch (Throwable $exception) {
            if ($exception instanceof ApiException) {
                throw $exception;
            }

            throw ApiException::serverError();
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $this->requireRoles($request, ['admin', 'operator']);

            $validated = $request->validate($this->storeRules());
            $client = Client::create($validated);

            return response()->json([
                'message' => 'Client  created successfully.',
                'client' => $client,
            ], 201);
        } catch (Throwable $exception) {
            if ($exception instanceof ApiException || $exception instanceof ValidationException) {
                throw $exception;
            }

            throw ApiException::serverError();
        }
    }

    public function update(Request $request, Client $client): JsonResponse
    {
        try {
            $this->requireRoles($request, ['admin', 'operator']);

            $validated = $request->validate($this->updateRules($client));
            $client->update($validated);

            return response()->json([
                'message' => 'Client updated successfully',
                'client' => $client->fresh(),
            ]);
        } catch (Throwable $exception) {
            if ($exception instanceof ApiException || $exception instanceof ValidationException) {
                throw $exception;
            }

            throw ApiException::serverError();
        }
    }

    public function destroy(Request $request, Client $client): JsonResponse
    {
        try {
            $this->requireRoles($request, ['admin']);

            $client->delete();

            return response()->json([
                'message' => 'Client delted successfully.',
            ]);
        } catch (Throwable $exception) {
            if ($exception instanceof ApiException) {
                throw $exception;
            }

            throw ApiException::serverError();
        }
    }

    private function storeRules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:255'],
            'cif' => [
                'required',
                'string',
                'max:255',
                Rule::unique('clients', 'cif'),
            ],
        ];
    }

    private function updateRules(Client $client): array
    {
        return [
            'nom' => ['sometimes', 'string', 'max:255'],
            'cif' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('clients', 'cif')->ignore($client->id),
            ],
        ];
    }
}
