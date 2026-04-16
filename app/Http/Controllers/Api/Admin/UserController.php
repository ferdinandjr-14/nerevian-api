<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Concerns\AuthorizesApiRequests;
use App\Http\Controllers\Controller;
use App\Models\Rol;
use App\Models\Usuari;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    use AuthorizesApiRequests;

    public function index(Request $request): JsonResponse
    {
        $this->requireRoles($request, ['admin']);

        $query = Usuari::query()->with(['rol', 'client'])->orderBy('id');

        if ($request->filled('rol')) {
            $query->whereHas('rol', function ($builder) use ($request): void {
                $builder->where('rol', $request->string('rol')->toString());
            });
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->integer('client_id'));
        }

        return response()->json($query->paginate((int) $request->integer('per_page', 15)));
    }

    public function show(Request $request, Usuari $usuari): JsonResponse
    {
        $this->requireRoles($request, ['admin']);

        return response()->json([
            'user' => $usuari->load(['rol', 'client']),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->requireRoles($request, ['admin']);

        $validated = $request->validate($this->rules($request));
        $validated = $this->normalizePayload($validated);

        $usuari = Usuari::create($validated);

        return response()->json([
            'message' => 'User created successfully.',
            'user' => $usuari->load(['rol', 'client']),
        ], 201);
    }

    public function update(Request $request, Usuari $usuari): JsonResponse
    {
        $this->requireRoles($request, ['admin']);

        $validated = $request->validate($this->rules($request, $usuari));
        $validated = $this->normalizePayload($validated, $usuari);

        if (empty($validated['contrasenya'] ?? null)) {
            unset($validated['contrasenya']);
        }

        $usuari->update($validated);

        return response()->json([
            'message' => 'User updated successfully.',
            'user' => $usuari->fresh()->load(['rol', 'client']),
        ]);
    }

    public function destroy(Request $request, Usuari $usuari): JsonResponse
    {
        $admin = $this->requireRoles($request, ['admin']);

        abort_if($admin->id === $usuari->id, 422, 'You cannot delete your own account.');

        $usuari->delete();

        return response()->json([
            'message' => 'User deleted successfully.',
        ]);
    }

    private function rules(Request $request, ?Usuari $usuari = null): array
    {
        $passwordRules = $usuari
            ? ['nullable', 'string', 'min:8', 'confirmed']
            : ['required', 'string', 'min:8', 'confirmed'];
        $clientRoleId = $this->clientRoleId();

        return [
            'nom' => [$usuari ? 'sometimes' : 'required', 'string', 'max:255'],
            'cognoms' => ['nullable', 'string', 'max:255'],
            'correu' => [
                $usuari ? 'sometimes' : 'required',
                'string',
                'email',
                'max:255',
                Rule::unique('usuaris', 'correu')->ignore($usuari?->id),
            ],
            'contrasenya' => $passwordRules,
            'rol_id' => [$usuari ? 'sometimes' : 'required', 'integer', 'exists:rols,id'],
            'client_id' => [
                Rule::requiredIf(
                    fn(): bool => $request->has('rol_id') && (int) $request->input('rol_id') === $clientRoleId
                ),
                'nullable',
                'integer',
                'exists:clients,id',
            ],
        ];
    }

    private function normalizePayload(array $validated, ?Usuari $usuari = null): array
    {
        $clientRoleId = $this->clientRoleId();
        $resolvedRoleId = (int) ($validated['rol_id'] ?? $usuari?->rol_id ?? 0);
        $resolvedClientId = $validated['client_id'] ?? $usuari?->client_id;

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
}
