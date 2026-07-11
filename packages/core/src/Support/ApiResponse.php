<?php

namespace Starship\Core\Support;

use Illuminate\Http\JsonResponse;

final class ApiResponse
{
    public static function success(mixed $data = null, string $message = 'ok', int $code = 0): JsonResponse
    {
        return response()->json([
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ]);
    }

    public static function error(string $message, int $httpStatus = 400, int $code = 1, mixed $data = null): JsonResponse
    {
        return response()->json([
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ], $httpStatus);
    }
}
