<?php

namespace App\Exceptions;

use App\Http\Responses\ApiErrorResponse;
use Exception;
use Illuminate\Http\Response;

class RepositoryException extends Exception
{
    protected int $statusCode;

    public function __construct(
        string $message = 'Repository Exception',
        int $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
        $this->statusCode = $statusCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function render()
    {
        return ApiErrorResponse::make(
            'repository_error',
            $this->getMessage(),
            null,
            $this->statusCode
        );
    }
}