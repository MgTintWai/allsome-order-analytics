<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTO\OrderCsvLoadResult;
use App\Exceptions\OrderCsvFileException;

/**
 * Loads order rows from a CSV file (path-based, no database).
 * Implementations must validate every row and surface row-level issues.
 */
interface OrderCsvRepositoryInterface
{
    /**
     * Read and parse the file at the given path. Malformed data rows are collected in
     * {@see OrderCsvLoadResult::rowIssues}; only transport-level failures use exceptions.
     *
     * @throws OrderCsvFileException When the path is not a readable file, or cannot be read.
     */
    public function loadFromFile(string $absolutePath): OrderCsvLoadResult;
}
