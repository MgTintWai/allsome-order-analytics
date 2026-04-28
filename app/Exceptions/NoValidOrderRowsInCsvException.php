<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class NoValidOrderRowsInCsvException extends RuntimeException
{
    /**
     * @param  list<array{line: int, message: string}>  $rowIssues  Same shape as {@see \App\DTO\CsvRowIssue::toArray()}
     */
    public function __construct(
        public readonly array $rowIssues,
        string $message = 'No valid order rows in CSV',
    ) {
        parent::__construct($message);
    }
}
