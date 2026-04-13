<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesApiRequests;
use App\Http\Controllers\Controller;
use App\Models\Usuari;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use AuthorizesApiRequests;

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'correu' => ['required', 'string', 'email'],
            'contrasenya' => ['required', 'string'],
            'token_name' => ['nullable', 'string', 'max:255'],
        ]);

        $usuari = Usuari::query()
            ->with(['rol', 'client'])
            ->where('correu', $validated['correu'])
            ->first();

        if (! $usuari || ! Hash::check($validated['contrasenya'], $usuari->contrasenya)) {
            throw ValidationException::withMessages([
                'correu' => ['Email or password incorrect'],
            ]);
        }

        $token = $usuari->createToken($validated['token_name'] ?? $request->userAgent() ?? 'api-token')->plainTextToken;

        return response()->json([
            'message' => 'Log in successful',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $usuari,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $this->currentUser($request);

        return response()->json([
            'user' => $user->load(['rol', 'client']),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $this->currentUser($request);
        $token = $user->currentAccessToken();

        if ($token) {
            $token->delete();
        } else {
            $user->tokens()->delete();
        }

        return response()->json([
            'message' => 'Log out successful',
        ]);
    }
}
