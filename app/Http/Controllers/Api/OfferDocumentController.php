<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesApiRequests;
use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Oferta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OfferDocumentController extends Controller
{
    use AuthorizesApiRequests;

    public function index(Request $request, Oferta $oferta): JsonResponse
    {
        $user = $this->currentUser($request);
        $this->authorizeOfferAccess($user, $oferta);

        return response()->json([
            'documents' => $oferta->documents()->with('uploadedBy.rol')->latest()->get(),
        ]);
    }

    public function store(Request $request, Oferta $oferta): JsonResponse
    {
        $user = $this->requireRoles($request, ['operator', 'admin']);

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:20480'],
            'tipus' => ['nullable', 'string', 'max:50'],
        ]);

        $file = $validated['file'];
        $path = $file->store("documents/ofertes/{$oferta->id}", 'local');

        $document = Document::create([
            'oferta_id' => $oferta->id,
            'uploaded_by_id' => $user->id,
            'tipus' => $validated['tipus'] ?? 'offer_attachment',
            'nom_original' => $file->getClientOriginalName(),
            'disk' => 'local',
            'path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'mida' => $file->getSize(),
        ]);

        return response()->json([
            'message' => 'Document pujat correctament.',
            'document' => $document->load('uploadedBy.rol'),
        ], 201);
    }

    public function download(Request $request, Oferta $oferta, Document $document): StreamedResponse
    {
        $user = $this->currentUser($request);
        $this->authorizeOfferAccess($user, $oferta);

        abort_if($document->oferta_id !== $oferta->id, 404, 'Document not found for this offer.');

        return Storage::disk($document->disk)->download($document->path, $document->nom_original);
    }
}
