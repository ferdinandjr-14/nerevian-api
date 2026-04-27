<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\AuthorizesApiRequests;
use App\Http\Controllers\Controller;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Throwable;

class SupersetController extends Controller
{
    use AuthorizesApiRequests;

    public function guestToken(Request $request): JsonResponse
    {
        try {
            $user = $this->currentUser($request);
            $validated = $request->validate([
                'dashboard_id' => ['nullable', 'string', 'max:255'],
            ]);

            $dashboardId = $validated['dashboard_id'] ?? config('services.superset.dashboard_id');
            if (blank($dashboardId)) {
                throw ApiException::make('Superset dashboard id is not configured.', 422);
            }

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
                throw ApiException::make('Unable to connect to Superset.', 502);
            }

            if ($response->failed()) {
                throw ApiException::make(
                    $this->resolveSupersetError($response, 'Unable to generate Superset guest token.'),
                    502
                );
            }

            $guestToken = $response->json('token');
            if (blank($guestToken)) {
                throw ApiException::make('Superset did not return a guest token.', 502);
            }

            return response()->json([
                'token' => $guestToken,
                'dashboard_id' => $dashboardId,
                'superset_url' => $this->supersetUrl(),
            ]);
        } catch (Throwable $exception) {
            if ($exception instanceof ApiException || $exception instanceof ValidationException) {
                throw $exception;
            }

            throw ApiException::serverError();
        }
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
            throw ApiException::make('Unable to connect to Superset.', 502);
        }

        if ($response->failed()) {
            throw ApiException::make(
                $this->resolveSupersetError($response, 'Unable to log in to Superset.'),
                502
            );
        }

        $accessToken = $response->json('access_token');
        if (blank($accessToken)) {
            throw ApiException::make('Superset did not return an access token.', 502);
        }

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
