<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Exceptions\OrderCsvFileException;

/**
 * Infrastructure: read a text file as lines only (no CSV semantics).
 */
interface CsvFileReaderInterface
{
    /**
     * Read the entire file; each element is one line (no trailing newline), same as
     * `file($path, FILE_IGNORE_NEW_LINES)`.
     *
     * @return list<string>
     *
     * @throws OrderCsvFileException If the path is not a readable file or cannot be read.
     */
    public function readRawLines(string $absolutePath): array;
}
