<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\AuthorizesApiRequests;
use App\Http\Controllers\Controller;
use App\Models\Usuari;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Throwable;

class AuthController extends Controller
{
    use AuthorizesApiRequests;

    public function login(Request $request): JsonResponse
    {
        try {
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
        } catch (Throwable $exception) {
            if ($exception instanceof ApiException || $exception instanceof ValidationException) {
                throw $exception;
            }

            throw ApiException::serverError();
        }
    }

    public function me(Request $request): JsonResponse
    {
        try {
            $user = $this->currentUser($request);

            return response()->json([
                'user' => $user->load(['rol', 'client']),
            ]);
        } catch (Throwable $exception) {
            if ($exception instanceof ApiException) {
                throw $exception;
            }

            throw ApiException::serverError();
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $user = $this->currentUser($request);

            $user->tokens()->delete();

            return response()->json([
                'message' => 'Log out successful',
            ]);
        } catch (Throwable $exception) {
            if ($exception instanceof ApiException) {
                throw $exception;
            }

            throw ApiException::serverError();
        }
    }
}
