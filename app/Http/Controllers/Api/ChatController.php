<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\AuthorizesApiRequests;
use App\Http\Controllers\Controller;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Throwable;

class ChatController extends Controller
{
    use AuthorizesApiRequests;

    public function send(Request $request): JsonResponse
    {
        try {
            $user = $this->currentUser($request);
            $validated = $request->validate([
                'message' => ['required', 'string', 'max:4000'],
            ]);

            $webhookUrl = config('services.n8n.webhook_url');
            if (blank($webhookUrl)) {
                throw ApiException::make('N8N webhook url is not configured.', 500);
            }

            try {
                $response = Http::acceptJson()
                    ->asJson()
                    ->timeout(240)
                    ->post($webhookUrl, [
                        'message' => $validated['message'],
                        'user' => [
                            'id' => $user->id,
                            'email' => $user->correu,
                            'name' => trim(sprintf('%s %s', $user->nom, $user->cognoms)),
                        ],
                    ]);
            } catch (ConnectionException) {
                throw ApiException::make('Unable to connect to the chatbot service.', 502);
            }

            if ($response->failed()) {
                throw ApiException::make('The chatbot service returned an error.', 502);
            }

            return response()->json([
                'reply' => $response->json('output')
                    ?? $response->json('reply')
                    ?? $response->json('message')
                    ?? trim($response->body()),
            ]);
        } catch (Throwable $exception) {
            if ($exception instanceof ApiException || $exception instanceof ValidationException) {
                throw $exception;
            }

            throw ApiException::serverError();
        }
    }
}
