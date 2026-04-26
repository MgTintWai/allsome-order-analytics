<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Http\Responses\ApiErrorResponse;
use App\Support\OrderUploadConstraints;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Validation\ValidationException;

/**
 * API JSON errors (see bootstrap/app.php). Registered after Laravel’s kernel exists.
 *
 * PostTooLargeException: thrown when the request is within PHP’s post_max_size but the
 * Content-Length still exceeds the limit Laravel checks (ValidatePostSize). This handler
 * cannot stop raw PHP *warnings* from post_max (see public/index.php + php.ini); it only
 * shapes the 413 response once the framework can handle the request.
 */
final class Handler
{
    public static function register(Exceptions $exceptions): void
    {
        $exceptions->renderable(function (PostTooLargeException $e, $request) {
            return self::forApi(
                $request,
                ApiErrorResponse::make(
                    'request_entity_too_large',
                    'Uploaded file exceeds the maximum allowed size ('.OrderUploadConstraints::maxUploadSizeMegabytes().' MB).',
                    null,
                    413
                )
            );
        });

        $exceptions->renderable(function (ValidationException $e, $request) {
            return self::forApi(
                $request,
                ApiErrorResponse::make(
                    'validation_error',
                    'Invalid input',
                    $e->errors(),
                    422
                )
            );
        });

        $exceptions->renderable(function (OrderCsvFileException $e, $request) {
            return self::forApi(
                $request,
                ApiErrorResponse::make(
                    'order_file_unreadable',
                    $e->getMessage(),
                    null,
                    404
                )
            );
        });

        $exceptions->renderable(function (NoValidOrderRowsInCsvException $e, $request) {
            return self::forApi(
                $request,
                ApiErrorResponse::make(
                    'no_valid_order_rows',
                    $e->getMessage(),
                    [
                        'row_errors' => $e->rowErrors,
                    ],
                    422
                )
            );
        });
    }

    private static function forApi($request, $response)
    {
        if (! $request->is('api/*') && ! $request->expectsJson() && ! $request->wantsJson()) {
            return null;
        }

        return $response;
    }
}
