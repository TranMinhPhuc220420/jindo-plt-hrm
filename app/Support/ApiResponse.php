<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\AbstractPaginator;

class ApiResponse
{
    /**
     * Build a successful API envelope response.
     *
     * @param  array<string, mixed>  $meta
     */
    public static function success(
        mixed $data = null,
        ?string $message = null,
        int $status = 200,
        array $meta = [],
    ): JsonResponse {
        $payload = [
            'success' => true,
            'data' => $data,
        ];

        if ($message !== null) {
            $payload['message'] = $message;
        }

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public static function created(
        mixed $data = null,
        ?string $message = null,
        array $meta = [],
    ): JsonResponse {
        return self::success($data, $message, 201, $meta);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public static function accepted(
        mixed $data = null,
        ?string $message = null,
        array $meta = [],
    ): JsonResponse {
        return self::success($data, $message, 202, $meta);
    }

    public static function paginated(
        AbstractPaginator $paginator,
        ?string $message = null,
    ): JsonResponse {
        return self::success(
            data: $paginator->items(),
            message: $message,
            meta: [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        );
    }

    /**
     * Build an error API envelope response.
     *
     * @param  array<string, array<int, string>|string>|null  $errors
     * @param  array<string, mixed>  $meta
     */
    public static function error(
        string $message,
        int $status = 400,
        ?string $errorCode = null,
        ?array $errors = null,
        array $meta = [],
    ): JsonResponse {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($errorCode !== null) {
            $payload['error_code'] = $errorCode;
        }

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }
}
