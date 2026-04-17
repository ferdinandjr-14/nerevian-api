<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesApiRequests;
use App\Http\Controllers\Controller;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SupersetController extends Controller
{
    use AuthorizesApiRequests;

    public function guestToken(Request $request): JsonResponse
    {
        $user = $this->currentUser($request);
        $validated = $request->validate([
            'dashboard_id' => ['nullable', 'string', 'max:255'],
        ]);

        $dashboardId = $validated['dashboard_id'] ?? config('services.superset.dashboard_id');
        abort_if(blank($dashboardId), 422, 'Superset dashboard id is not configured.');

        $accessToken = $this->loginToSuperset();

        try {
            $response = $this->supersetRequest()
                ->withToken($accessToken)
                ->post('/api/v1/security/guest_token', [
                    'user' => [
                        'username' => $user->correu ?? sprintf('user-%s', $user->id),
                        'first_name' => $user->nom,
                        'last_name' => $user->cognoms,
                    ],
                    'resources' => [
                        [
                            'type' => 'dashboard',
                            'id' => $dashboardId,
                        ],
                    ],
                    'rls' => [],
                ]);
        } catch (ConnectionException) {
            abort(502, 'Unable to connect to Superset.');
        }

        if ($response->failed()) {
            abort(502, $this->resolveSupersetError($response, 'Unable to generate Superset guest token.'));
        }

        $guestToken = $response->json('token');
        abort_if(blank($guestToken), 502, 'Superset did not return a guest token.');

        return response()->json([
            'token' => $guestToken,
            'dashboard_id' => $dashboardId,
            'superset_url' => $this->supersetUrl(),
        ]);
    }

    private function loginToSuperset(): string
    {
        try {
            $response = $this->supersetRequest()->post('/api/v1/security/login', [
                'username' => config('services.superset.username', 'admin'),
                'password' => config('services.superset.password', 'admin'),
                'provider' => 'db',
                'refresh' => true,
            ]);
        } catch (ConnectionException) {
            abort(502, 'Unable to connect to Superset.');
        }

        if ($response->failed()) {
            abort(502, $this->resolveSupersetError($response, 'Unable to log in to Superset.'));
        }

        $accessToken = $response->json('access_token');
        abort_if(blank($accessToken), 502, 'Superset did not return an access token.');

        return $accessToken;
    }

    private function supersetRequest()
    {
        return Http::baseUrl($this->supersetUrl())
            ->acceptJson()
            ->asJson()
            ->timeout(15);
    }

    private function supersetUrl(): string
    {
        return rtrim(config('services.superset.url', 'http://localhost:8088'), '/');
    }

    private function resolveSupersetError(Response $response, string $fallbackMessage): string
    {
        $message = $response->json('message')
            ?? $response->json('errors.0.message')
            ?? $response->json('errors.0.error')
            ?? $response->json('result.message');

        return is_string($message) && $message !== ''
            ? $message
            : $fallbackMessage;
    }
}
