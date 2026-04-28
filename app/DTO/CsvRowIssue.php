<?php

declare(strict_types=1);

namespace App\DTO;

/**
 * DTO (not an {@see \Exception}): structured feedback for one CSV row that failed validation.
 * Used to build API payloads (e.g. warnings) without interrupting control flow—fatal file problems
 * stay in {@see \App\Exceptions\OrderCsvFileException} / {@see \App\Exceptions\NoValidOrderRowsInCsvException}.
 */
final readonly class CsvRowIssue
{
    /**
     * @param  positive-int  $lineNumber  1-based line in the file
     * @param  non-empty-string  $message  Human-readable validation message (stable for clients)
     */
    public function __construct(
        public int $lineNumber,
        public string $message,
    ) {}

    /**
     * Wire format for nested JSON (e.g. `warnings`, `details.row_errors`).
     *
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
