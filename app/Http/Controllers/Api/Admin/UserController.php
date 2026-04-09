<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Concerns\AuthorizesApiRequests;
use App\Http\Controllers\Controller;
use App\Models\Usuari;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    use AuthorizesApiRequests;

    public function index(Request $request): JsonResponse
    {
        $this->requireRoles($request, ['admin']);

        $query = Usuari::query()->with(['rol'])->orderBy('id');

        if ($request->filled('rol')) {
            $query->whereHas('rol', function ($builder) use ($request): void {
                $builder->where('rol', $request->string('rol')->toString());
            });
        }

        return response()->json($query->paginate((int) $request->integer('per_page', 15)));
    }

    public function show(Request $request, Usuari $usuari): JsonResponse
    {
        $this->requireRoles($request, ['admin']);

        return response()->json([
            'user' => $usuari->load(['rol']),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->requireRoles($request, ['admin']);

        $validated = $request->validate($this->rules());

        $usuari = Usuari::create($validated);

        return response()->json([
            'message' => 'Usuari creat correctament.',
            'user' => $usuari->load(['rol']),
        ], 201);
    }

    public function update(Request $request, Usuari $usuari): JsonResponse
    {
        $this->requireRoles($request, ['admin']);

        $validated = $request->validate($this->rules($usuari));

        if (empty($validated['contrasenya'] ?? null)) {
            unset($validated['contrasenya']);
        }

        $usuari->update($validated);

        return response()->json([
            'message' => 'Usuari actualitzat correctament.',
            'user' => $usuari->fresh()->load(['rol']),
        ]);
    }

    public function destroy(Request $request, Usuari $usuari): JsonResponse
    {
        $admin = $this->requireRoles($request, ['admin']);

        abort_if($admin->id === $usuari->id, 422, 'You cannot delete your own account.');

        $usuari->delete();

        return response()->json([
            'message' => 'Usuari eliminat correctament.',
        ]);
    }

    private function rules(?Usuari $usuari = null): array
    {
        $passwordRules = $usuari
            ? ['nullable', 'string', 'min:8', 'confirmed']
            : ['required', 'string', 'min:8', 'confirmed'];

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
        ];
    }
}
