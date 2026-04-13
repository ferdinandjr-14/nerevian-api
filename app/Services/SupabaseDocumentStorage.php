<?php

namespace App\Services;

use App\Models\DniUsuari;
use App\Models\DocumentOferta;
use App\Models\Oferta;
use App\Models\Usuari;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SupabaseDocumentStorage
{
    public function uploadDni(Usuari $user, UploadedFile $file): DniUsuari
    {
        $path = $this->buildDniPath($user, $file);
        $currentDocument = $user->dniDocument()->first();

        $this->uploadFile($this->bucket('dni'), $path, $file);

        if ($currentDocument !== null && $currentDocument->path !== $path) {
            $this->deleteFile($this->bucket('dni'), $currentDocument->path);
        }

        return DniUsuari::query()->updateOrCreate(
            ['usuari_id' => $user->id],
            ['path' => $path],
        );
    }

    public function getDni(Usuari $user): ?array
    {
        $document = $user->dniDocument()->first();

        if ($document === null) {
            return null;
        }

        return $this->toDocumentPayload($this->bucket('dni'), $document->path);
    }

    public function uploadOfferDocuments(Oferta $offer, array $files): Collection
    {
        $documents = collect();

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $path = $this->buildOfferPath($offer, $file);
            $this->uploadFile($this->bucket('offers'), $path, $file);

            $documents->push(
                DocumentOferta::query()->firstOrCreate(
                    [
                        'oferta_id' => $offer->id,
                        'path' => $path,
                    ]
                )
            );
        }

        return $documents;
    }

    public function getOfferDocuments(Oferta $offer): Collection
    {
        return $offer->documents()
            ->orderBy('id')
            ->get()
            ->map(fn (DocumentOferta $document): array => $this->toDocumentPayload($this->bucket('offers'), $document->path));
    }

    private function uploadFile(string $bucket, string $path, UploadedFile $file): void
    {
        $content = file_get_contents($file->getRealPath());

        if ($content === false) {
            throw new RequestException(Http::response([
                'message' => 'Unable to read uploaded file.',
            ], 500));
        }

        Http::withHeaders($this->headers([
            'Content-Type' => $file->getMimeType() ?: 'application/octet-stream',
            'x-upsert' => 'true',
        ]))
            ->withBody($content, $file->getMimeType() ?: 'application/octet-stream')
            ->post($this->endpoint("object/{$bucket}/{$path}"))
            ->throw();
    }

    private function deleteFile(string $bucket, string $path): void
    {
        Http::withHeaders($this->headers())
            ->delete($this->endpoint("object/{$bucket}/{$path}"));
    }

    private function createSignedUrl(string $bucket, string $path): string
    {
        $response = Http::withHeaders($this->headers())
            ->post($this->endpoint("object/sign/{$bucket}/{$path}"), [
                'expiresIn' => (int) config('services.supabase.signed_url_ttl', 3600),
            ])
            ->throw()
            ->json();

        $signedPath = $response['signedURL'] ?? $response['signedUrl'] ?? null;

        if (! is_string($signedPath) || $signedPath === '') {
            abort(500, 'Supabase did not return a signed URL.');
        }

        if (Str::startsWith($signedPath, ['http://', 'https://'])) {
            return $signedPath;
        }

        return rtrim(config('services.supabase.url'), '/').'/'.ltrim($signedPath, '/');
    }

    private function toDocumentPayload(string $bucket, string $path): array
    {
        return [
            'name' => $this->displayName($bucket, $path),
            'path' => $path,
            'download_url' => $this->createSignedUrl($bucket, $path),
        ];
    }

    private function buildDniPath(Usuari $user, UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $name = implode('_', array_filter([
            $user->id,
            $this->sanitizeSegment($user->nom),
            $this->sanitizeSegment($user->cognoms),
        ]));

        return $extension !== '' ? "{$name}.{$extension}" : $name;
    }

    private function buildOfferPath(Oferta $offer, UploadedFile $file): string
    {
        return $offer->id.'_'.$this->sanitizeFileName($file->getClientOriginalName());
    }

    private function displayName(string $bucket, string $path): string
    {
        $filename = basename($path);

        if ($bucket === $this->bucket('offers')) {
            $position = strpos($filename, '_');

            return $position === false ? $filename : substr($filename, $position + 1);
        }

        return $filename;
    }

    private function sanitizeFileName(string $filename): string
    {
        $baseName = pathinfo($filename, PATHINFO_FILENAME);
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $safeBaseName = $this->sanitizeSegment($baseName, 'file');

        if ($extension === '') {
            return $safeBaseName;
        }

        $safeExtension = Str::of($extension)->lower()->replaceMatches('/[^a-z0-9]+/', '')->toString();

        return $safeExtension === '' ? $safeBaseName : "{$safeBaseName}.{$safeExtension}";
    }

    private function sanitizeSegment(?string $value, string $fallback = 'document'): string
    {
        $sanitized = Str::of($value ?? '')
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->toString();

        return $sanitized !== '' ? $sanitized : $fallback;
    }

    private function headers(array $headers = []): array
    {
        $key = config('services.supabase.key');

        return array_merge([
            'apikey' => $key,
            'Authorization' => 'Bearer '.$key,
        ], $headers);
    }

    private function endpoint(string $path): string
    {
        return rtrim(config('services.supabase.url'), '/').'/'.ltrim($path, '/');
    }

    private function bucket(string $name): string
    {
        return config("services.supabase.buckets.{$name}", $name);
    }
}
