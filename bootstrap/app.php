<?php

use App\Exceptions\ApiException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectGuestsTo(fn (Request $request) => route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (TokenMismatchException $e, Request $request) {
            if ($request->is('logout')) {
                Auth::logout();

                return redirect()->route('login', ['expired' => 'session']);
            }

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sesi sudah kedaluwarsa. Muat ulang halaman dan coba lagi.',
                    'error_code' => 'CSRF_TOKEN_MISMATCH',
                    'errors' => (object) [],
                ], 419);
            }
        });
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated',
                    'error_code' => 'UNAUTHENTICATED',
                    'errors' => (object) [],
                ], 401);
            }
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'Forbidden',
                    'error_code' => 'FORBIDDEN',
                    'errors' => (object) [],
                ], 403);
            }
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'error_code' => 'VALIDATION_FAILED',
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Resource not found',
                    'error_code' => 'NOT_FOUND',
                    'errors' => (object) [],
                ], 404);
            }
        });

        $exceptions->render(function (ApiException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'error_code' => $e->getErrorCode(),
                    'errors' => (object) [],
                ], $e->getStatusCode());
            }
        });

        $exceptions->render(function (HttpException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                $statusCode = $e->getStatusCode();
                $headers = $e->getHeaders();
                $errorCode = $headers['X-Error-Code'] ?? match ($statusCode) {
                    403 => 'APPROVAL_NOT_ALLOWED',
                    404 => 'NOT_FOUND',
                    409 => 'CONFLICT',
                    422 => 'VALIDATION_FAILED',
                    default => 'HTTP_ERROR',
                };

                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'error_code' => $errorCode,
                    'errors' => (object) [],
                ], $statusCode);
            }
        });
    })->create();
