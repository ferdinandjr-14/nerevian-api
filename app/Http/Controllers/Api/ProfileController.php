<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesApiRequests;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    use AuthorizesApiRequests;

    public function show(Request $request): JsonResponse
    {
        $user = $this->currentUser($request);

        return response()->json([
            'user' => $user->load(['rol', 'client']),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $this->currentUser($request);

        $validated = $request->validate([
            'nom' => ['sometimes', 'string', 'max:255'],
            'cognoms' => ['nullable', 'string', 'max:255'],
            'correu' => ['sometimes', 'string', 'email', 'max:255', Rule::unique('usuaris', 'correu')->ignore($user->id)],
            'contrasenya' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        if (empty($validated['contrasenya'] ?? null)) {
            unset($validated['contrasenya']);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'Perfil actualitzat correctament.',
            'user' => $user->fresh()->load(['rol', 'client']),
        ]);
    }
}
