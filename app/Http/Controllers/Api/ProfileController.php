<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\AuthorizesApiRequests;
use App\Http\Controllers\Controller;
use App\Services\SupabaseDocumentStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProfileController extends Controller
{
    use AuthorizesApiRequests;

    private SupabaseDocumentStorage $documentStorage;

    public function __construct(SupabaseDocumentStorage $documentStorage)
    {
        $this->documentStorage = $documentStorage;
    }

    public function show(Request $request): JsonResponse
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

    public function update(Request $request): JsonResponse
    {
        try {
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
                'message' => 'Profile updated successfully',
                'user' => $user->fresh()->load(['rol', 'client']),
            ]);
        } catch (Throwable $exception) {
            if ($exception instanceof ApiException || $exception instanceof ValidationException) {
                throw $exception;
            }

            throw ApiException::serverError();
        }
    }

    public function uploadDni(Request $request): JsonResponse
    {
        try {
            $user = $this->currentUser($request);

            $validated = $request->validate([
                'dni' => ['required', 'file', 'max:10240'],
            ]);

            $document = $this->documentStorage->uploadDni($user, $validated['dni']);

            return response()->json([
                'message' => 'DNI uploaded successfully',
                'document' => $this->documentStorage->getDni($user),
                'path' => $document->path,
            ]);
        } catch (Throwable $exception) {
            if ($exception instanceof ApiException || $exception instanceof ValidationException) {
                throw $exception;
            }

            throw ApiException::serverError();
        }
    }

    public function dni(Request $request): JsonResponse
    {
        try {
            $user = $this->currentUser($request);

            return response()->json([
                'document' => $this->documentStorage->getDni($user),
            ]);
        } catch (Throwable $exception) {
            if ($exception instanceof ApiException) {
                throw $exception;
            }

            throw ApiException::serverError();
        }
    }
}
