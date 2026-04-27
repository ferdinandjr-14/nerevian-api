<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\AuthorizesApiRequests;
use App\Http\Controllers\Controller;
use App\Models\Rol;
use App\Models\Usuari;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class UserController extends Controller
{
    use AuthorizesApiRequests;

    public function index(Request $request): JsonResponse
    {
        try {
            $this->requireRoles($request, ['admin']);

            $query = Usuari::query()->with(['rol', 'client'])->orderBy('id');
            $role = (string) $request->input('rol', '');

            if ($role !== '') {
                $query->whereHas('rol', function ($builder) use ($role): void {
                    $builder->where('rol', $role);
                });
            }

            if ($request->filled('client_id')) {
                $query->where('client_id', (int) $request->input('client_id'));
            }

            return response()->json($query->paginate((int) $request->input('per_page', 15)));
        } catch (Throwable $exception) {
            if ($exception instanceof ApiException) {
                throw $exception;
            }

            throw ApiException::serverError();
        }
    }

    public function show(Request $request, Usuari $usuari): JsonResponse
    {
        try {
            $this->requireRoles($request, ['admin']);

            return response()->json([
                'user' => $usuari->load(['rol', 'client']),
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
            $this->requireRoles($request, ['admin']);

            $validated = $request->validate($this->storeRules($request));
            $validated = $this->normalizePayload($validated);

            $usuari = Usuari::create($validated);

            return response()->json([
                'message' => 'User created successfully.',
                'user' => $usuari->load(['rol', 'client']),
            ], 201);
        } catch (Throwable $exception) {
            if ($exception instanceof ApiException || $exception instanceof ValidationException) {
                throw $exception;
            }

            throw ApiException::serverError();
        }
    }

    public function update(Request $request, Usuari $usuari): JsonResponse
    {
        try {
            $this->requireRoles($request, ['admin']);

            $validated = $request->validate($this->updateRules($request, $usuari));
            $validated = $this->normalizePayload($validated, $usuari);

            if (empty($validated['contrasenya'] ?? null)) {
                unset($validated['contrasenya']);
            }

            $usuari->update($validated);

            return response()->json([
                'message' => 'User updated successfully.',
                'user' => $usuari->fresh()->load(['rol', 'client']),
            ]);
        } catch (Throwable $exception) {
            if ($exception instanceof ApiException || $exception instanceof ValidationException) {
                throw $exception;
            }

            throw ApiException::serverError();
        }
    }

    public function destroy(Request $request, Usuari $usuari): JsonResponse
    {
        try {
            $admin = $this->requireRoles($request, ['admin']);

            if ($admin->id === $usuari->id) {
                throw ApiException::make('You cannot delete your own account.', 422);
            }

            $usuari->delete();

            return response()->json([
                'message' => 'User deleted successfully.',
            ]);
        } catch (Throwable $exception) {
            if ($exception instanceof ApiException) {
                throw $exception;
            }

            throw ApiException::serverError();
        }
    }

    private function storeRules(Request $request): array
    { 
        return [
            'nom' => ['required', 'string', 'max:255'],
            'cognoms' => ['nullable', 'string', 'max:255'],
            'correu' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('usuaris', 'correu'),
            ],
            'contrasenya' => ['required', 'string', 'min:8', 'confirmed'],
            'rol_id' => ['required', 'integer', 'exists:rols,id'],
            'client_id' => $this->clientIdRules($request),
        ];
    }

    private function updateRules(Request $request, Usuari $usuari): array
    { 
        return [
            'nom' => ['sometimes', 'string', 'max:255'],
            'cognoms' => ['nullable', 'string', 'max:255'],
            'correu' => [
                'sometimes',
                'string',
                'email',
                'max:255',
                Rule::unique('usuaris', 'correu')->ignore($usuari->id),
            ],
            'contrasenya' => ['nullable', 'string', 'min:8', 'confirmed'],
            'rol_id' => ['sometimes', 'integer', 'exists:rols,id'],
            'client_id' => $this->clientIdRules($request),
        ];
    }

    private function normalizePayload(array $validated, ?Usuari $usuari = null): array
    {
        $clientRoleId = $this->clientRoleId();
        $resolvedRoleId = 0;
        $resolvedClientId = null;

        if (array_key_exists('rol_id', $validated)) {
            $resolvedRoleId = (int) $validated['rol_id'];
        } elseif ($usuari !== null) {
            $resolvedRoleId = (int) $usuari->rol_id;
        }

        if (array_key_exists('client_id', $validated)) {
            $resolvedClientId = $validated['client_id'];
        } elseif ($usuari !== null) {
            $resolvedClientId = $usuari->client_id;
        }

        if ($resolvedRoleId === $clientRoleId && empty($resolvedClientId)) {
            throw ValidationException::withMessages([
                'client_id' => ['Client is required when the selected role is client.'],
            ]);
        }

        if ($resolvedRoleId !== $clientRoleId) {
            $validated['client_id'] = null;
        }

        return $validated;
    }

    private function clientRoleId(): int
    {
        return (int) Rol::query()->where('rol', 'client')->value('id');
    }

    private function clientIdRules(Request $request): array
    {
        $rules = ['nullable', 'integer', 'exists:clients,id'];

        if ($request->has('rol_id') && (int) $request->input('rol_id') === $this->clientRoleId()) {
            array_unshift($rules, 'required');
        }

        return $rules;
    }
}
