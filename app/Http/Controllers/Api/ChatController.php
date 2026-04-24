<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesApiRequests;
use App\Http\Controllers\Controller;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ChatController extends Controller
{
    use AuthorizesApiRequests;

    public function send(Request $request): JsonResponse
    {
        $user = $this->currentUser($request);
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
        ]);

        $webhookUrl = config('services.n8n.webhook_url');
        abort_if(blank($webhookUrl), 500, 'N8N webhook url is not configured.');

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
            abort(502, 'Unable to connect to the chatbot service.');
        }

        if ($response->failed()) {
            abort(502, 'The chatbot service returned an error.');
        }

        return response()->json([
            'reply' => $response->json('output')
                ?? $response->json('reply')
                ?? $response->json('message')
                ?? trim($response->body()),
        ]);
    }
}
