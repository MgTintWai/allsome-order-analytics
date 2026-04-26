<?php

namespace App\Contracts;

use App\Exceptions\OrderCsvFileException;

/**
 * Loads order rows from a CSV file (path-based, no database).
 * Implementations must validate every row and surface row-level issues.
 */
interface OrderCsvRepositoryInterface
{
    /**
     * @return array{
     *     lines: list<array{order_id: int, sku: string, quantity: int, price: float}>,
     *     row_errors: list<array{line: int, message: string}>
     * }
     *
     * @throws OrderCsvFileException When the file is missing, not readable, or not a file.
     */
    public function loadFromFile(string $absolutePath): array;
}
