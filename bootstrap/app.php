<?php

use App\Exceptions\DomainException;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetLocale;
use App\Support\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
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
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->statefulApi();

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            SetLocale::class,
        ]);

        $middleware->api(append: [
            SetLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            if ($e instanceof ValidationException) {
                return ApiResponse::error(
                    message: 'Validation failed.',
                    status: 422,
                    errorCode: 'VALIDATION_FAILED',
                    errors: $e->errors(),
                );
            }

            if ($e instanceof AuthenticationException) {
                return ApiResponse::error(
                    message: 'Unauthenticated.',
                    status: 401,
                    errorCode: 'UNAUTHENTICATED',
                );
            }

            if ($e instanceof AuthorizationException) {
                return ApiResponse::error(
                    message: $e->getMessage() !== '' ? $e->getMessage() : 'Forbidden.',
                    status: 403,
                    errorCode: 'FORBIDDEN',
                );
            }

            if ($e instanceof ModelNotFoundException) {
                return ApiResponse::error(
                    message: 'Resource not found.',
                    status: 404,
                    errorCode: 'NOT_FOUND',
                );
            }

            if ($e instanceof DomainException) {
                return ApiResponse::error(
                    message: $e->getMessage(),
                    status: $e->status(),
                    errorCode: $e->errorCode(),
                    errors: $e->errors(),
                    meta: $e->meta(),
                );
            }

            if ($e instanceof HttpExceptionInterface) {
                $status = $e->getStatusCode();
                $message = $e->getMessage() !== '' ? $e->getMessage() : match ($status) {
                    404 => 'Resource not found.',
                    403 => 'Forbidden.',
                    401 => 'Unauthenticated.',
                    502 => 'Bad gateway.',
                    503 => 'Service unavailable.',
                    default => 'Request failed.',
                };

                $meta = [];
                $headers = $e->getHeaders();
                $retryAfter = $headers['Retry-After'] ?? $headers['retry-after'] ?? null;

                if ($retryAfter !== null && $retryAfter !== '') {
                    $meta['retry_after'] = is_numeric($retryAfter)
                        ? (int) $retryAfter
                        : $retryAfter;
                }

                return ApiResponse::error(
                    message: $message,
                    status: $status,
                    errorCode: match ($status) {
                        404 => 'NOT_FOUND',
                        403 => 'FORBIDDEN',
                        401 => 'UNAUTHENTICATED',
                        429 => 'TOO_MANY_REQUESTS',
                        502 => 'BAD_GATEWAY',
                        503 => 'SERVICE_UNAVAILABLE',
                        default => null,
                    },
                    meta: $meta,
                );
            }

            if (! config('app.debug')) {
                return ApiResponse::error(
                    message: 'Server error.',
                    status: 500,
                    errorCode: 'SERVER_ERROR',
                );
            }

            return null;
        });
    })->create();
