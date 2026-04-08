<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesApiRequests;
use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfileController extends Controller
{
    use AuthorizesApiRequests;

    public function show(Request $request): JsonResponse
    {
        $user = $this->currentUser($request);

        return response()->json([
            'user' => $user->load(['rol', 'client', 'documents' => function ($query): void {
                $query->where('tipus', 'dni')->latest();
            }]),
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

    public function uploadDni(Request $request): JsonResponse
    {
        $user = $this->currentUser($request);

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:10240'],
        ]);

        $file = $validated['file'];
        $path = $file->store("documents/usuaris/{$user->id}/dni", 'local');

        $document = Document::create([
            'usuari_id' => $user->id,
            'uploaded_by_id' => $user->id,
            'tipus' => 'dni',
            'nom_original' => $file->getClientOriginalName(),
            'disk' => 'local',
            'path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'mida' => $file->getSize(),
        ]);

        $user->update([
            'dni_document_path' => $path,
        ]);

        return response()->json([
            'message' => 'DNI pujat correctament.',
            'document' => $document,
        ], 201);
    }

    public function downloadDni(Request $request, Document $document): StreamedResponse
    {
        $user = $this->currentUser($request);

        abort_if(
            $document->tipus !== 'dni' || ($document->usuari_id !== $user->id && ! $this->hasRole($user, 'admin')),
            403,
            'You are not allowed to access this document.'
        );

        return Storage::disk($document->disk)->download($document->path, $document->nom_original);
    }
}
