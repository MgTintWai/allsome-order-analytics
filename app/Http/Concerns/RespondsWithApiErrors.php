<?php

declare(strict_types=1);

namespace App\Http\Concerns;

use App\Http\Responses\ApiErrorResponse;
use Illuminate\Http\JsonResponse;

trait RespondsWithApiErrors
{
    /**
     * @param  array<string, mixed>|null  $details
     */
    protected function apiError(
        string $type,
        string $message,
        ?array $details = null,
        int $status = 500
    ): JsonResponse {
        return ApiErrorResponse::make($type, $message, $details, $status);
    }
}
