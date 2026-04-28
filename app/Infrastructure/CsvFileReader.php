<?php

declare(strict_types=1);

namespace App\Infrastructure;

use App\Contracts\CsvFileReaderInterface;
use App\Exceptions\OrderCsvFileException;

/**
 * Filesystem access only. No `str_getcsv`, no business rules.
 */
final class CsvFileReader implements CsvFileReaderInterface
{
    public function readRawLines(string $absolutePath): array
    {
        if (! is_file($absolutePath) || ! is_readable($absolutePath)) {
            throw new OrderCsvFileException("CSV file not found or not readable: {$absolutePath}");
        }

        $lines = @file($absolutePath, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            throw new OrderCsvFileException("Unable to read CSV file: {$absolutePath}");
        }

        return $lines;
    }
}
