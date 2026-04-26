<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

final class ApiErrorResponse
{
    /**
     * @param  array<string, mixed>|null  $details
     */
    public static function make(
        string $type,
        string $message,
        ?array $details = null,
        int $status = 500
    ): JsonResponse {
        $error = [
            'type' => $type,
            'message' => $message,
        ];
        if ($details !== null) {
            $error['details'] = $details;
        }

        return response()->json(['error' => $error], $status);
    }
}
