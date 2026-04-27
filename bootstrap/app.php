<?php

use App\Exceptions\ApiException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $throwable): bool {
            return $request->is('api/*') || $request->expectsJson();
        });

        $exceptions->render(function (ApiException $exception, Request $request) {
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => $exception->errors(),
            ], $exception->status());
        });

        $exceptions->render(function (ValidationException $exception, Request $request) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $exception->errors(),
            ], $exception->status);
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            return response()->json([
                'message' => 'Unauthenticated.',
                'errors' => [],
            ], 401);
        });

        $exceptions->render(function (AuthorizationException $exception, Request $request) {
            return response()->json([
                'message' => $exception->getMessage() ?: 'This action is unauthorized.',
                'errors' => [],
            ], 403);
        });

        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) {
            return response()->json([
                'message' => $exception->getMessage() ?: 'HTTP request failed.',
                'errors' => [],
            ], $exception->getStatusCode());
        });

        $exceptions->render(function (Throwable $exception, Request $request) {
            return response()->json([
                'message' => 'Server error.',
                'errors' => [],
            ], 500);
        });
    })->create();
