<?php

declare(strict_types=1);

namespace App\DTO;

/**
 * DTO: a single data-row validation problem (line number + message for API warnings / errors).
 */
final readonly class CsvRowError
{
    /**
     * @param  positive-int  $lineNumber
     * @param  non-empty-string  $message
     */
    public function __construct(
        public int $lineNumber,
        public string $message,
    ) {}

    /**
     * @return array{line: int, message: string}
     */
    public function toArray(): array
    {
        return [
            'line' => $this->lineNumber,
            'message' => $this->message,
        ];
    }
}