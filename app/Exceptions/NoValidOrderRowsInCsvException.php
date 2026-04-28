<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class NoValidOrderRowsInCsvException extends RuntimeException
{
    /**
     * @param  list<array{line: int, message: string}>  $rowErrors
     */
    public function __construct(
        public readonly array $rowErrors,
        string $message = 'No valid order rows in CSV',
    ) {
        parent::__construct($message);
    }
}