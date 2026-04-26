<?php

namespace App\Repositories;

use App\Contracts\OrderCsvRepositoryInterface;
use App\Exceptions\OrderCsvFileException;
use App\Support\OrderUploadConstraints;

/**
 * Loads and validates order data from a CSV on disk. This is separate from the Eloquent
 * {@see BaseRepository} stack used for model CRUD in other parts of the app.
 */
class OrderCsvRepository implements OrderCsvRepositoryInterface
{
    private const EXPECTED_HEADERS = ['order_id', 'sku', 'quantity', 'price'];

    public function loadFromFile(string $absolutePath): array
    {
        if (! is_file($absolutePath) || ! is_readable($absolutePath)) {
            throw new OrderCsvFileException("CSV file not found or not readable: {$absolutePath}");
        }

        $raw = @file($absolutePath, FILE_IGNORE_NEW_LINES);
        if ($raw === false) {
            throw new OrderCsvFileException("Unable to read CSV file: {$absolutePath}");
        }

        if ($raw === []) {
            return [
                'lines' => [],
                'row_errors' => [['line' => 1, 'message' => 'CSV is empty']],
            ];
        }

        $headerLine = str_getcsv($raw[0]);
        $headerLine = array_map(fn (string $h) => strtolower(trim($h, " \t\n\r\0\x0B\u{FEFF}")), $headerLine);
        if (! $this->headersMatch($headerLine)) {
            return [
                'lines' => [],
                'row_errors' => [['line' => 1, 'message' => 'Invalid header row: expected order_id, sku, quantity, price']],
            ];
        }

        $orderLines = [];
        $rowErrors = [];
        for ($i = 1, $c = count($raw); $i < $c; $i++) {
            $lineNumber = $i + 1;
            if (trim($raw[$i]) === '') {
                continue;
            }

            $row = str_getcsv($raw[$i]);
            if ($this->isRowVisiblyEmpty($row)) {
                continue;
            }

            $parsed = $this->parseRow($row, $lineNumber);
            if (is_array($parsed) && isset($parsed['line'], $parsed['message'])) {
                $rowErrors[] = $parsed;
            } else {
                $orderLines[] = $parsed;
            }
        }

        return [
            'lines' => $orderLines,
            'row_errors' => $rowErrors,
        ];
    }

    /**
     * @param  list<string>  $headerLine
     */
    private function headersMatch(array $headerLine): bool
    {
        if (count($headerLine) < count(self::EXPECTED_HEADERS)) {
            return false;
        }

        foreach (self::EXPECTED_HEADERS as $i => $expected) {
            if (($headerLine[$i] ?? '') !== $expected) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<string|null>  $row
     */
    private function isRowVisiblyEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if ($cell !== null && trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<string|null>  $row
     * @return array{order_id: int, sku: string, quantity: int, price: float}|array{line: int, message: string}
     */
    private function parseRow(array $row, int $lineNumber): array
    {
        if (count($row) < OrderUploadConstraints::EXPECTED_CSV_COLUMN_COUNT) {
            $n = OrderUploadConstraints::EXPECTED_CSV_COLUMN_COUNT;

            return ['line' => $lineNumber, 'message' => "Not enough columns (expected {$n})"];
        }

        $orderIdRaw = trim((string) $row[0]);
        if ($orderIdRaw === '' || ! ctype_digit($orderIdRaw)) {
            return ['line' => $lineNumber, 'message' => 'order_id must be a positive integer'];
        }

        $orderId = (int) $orderIdRaw;
        if ($orderId < 1) {
            return ['line' => $lineNumber, 'message' => 'order_id must be a positive integer'];
        }

        $sku = trim((string) $row[1]);
        if ($sku === '') {
            return ['line' => $lineNumber, 'message' => 'sku is required'];
        }

        $quantityRaw = trim((string) $row[2]);
        if ($quantityRaw === '' || ! ctype_digit($quantityRaw)) {
            return ['line' => $lineNumber, 'message' => 'quantity must be a positive integer'];
        }

        $quantity = (int) $quantityRaw;
        if ($quantity < 1) {
            return ['line' => $lineNumber, 'message' => 'quantity must be a positive integer'];
        }

        $priceRaw = trim((string) $row[3]);
        if ($priceRaw === '' || ! is_numeric($priceRaw)) {
            return ['line' => $lineNumber, 'message' => 'price must be a non-negative number'];
        }

        $price = (float) $priceRaw;
        if ($price < 0 || is_nan($price) || is_infinite($price)) {
            return ['line' => $lineNumber, 'message' => 'price must be a non-negative number'];
        }

        return [
            'order_id' => $orderId,
            'sku' => $sku,
            'quantity' => $quantity,
            'price' => $price,
        ];
    }
}
