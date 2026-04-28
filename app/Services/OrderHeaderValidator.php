<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\CsvRowIssue;

/**
 * Validates the first row of the orders CSV: expected column names, BOM/whitespace, lowercase.
 */
final class OrderHeaderValidator
{
    private const EXPECTED_HEADERS = ['order_id', 'sku', 'quantity', 'price'];

    /**
     * @param  list<string|null>  $headerCells  First data row after `str_getcsv` of line 1.
     */
    public function validateHeaderOrIssue(array $headerCells): ?CsvRowIssue
    {
        $headerLine = array_map(
            function ($cell): string {
                return strtolower(trim((string) $cell, " \t\n\r\0\x0B\u{FEFF}"));
            },
            $headerCells
        );

        if (count($headerLine) < count(self::EXPECTED_HEADERS)) {
            return new CsvRowIssue(1, 'Invalid header row: expected order_id, sku, quantity, price');
        }

        foreach (self::EXPECTED_HEADERS as $index => $expectedHeader) {
            if (($headerLine[$index] ?? '') !== $expectedHeader) {
                return new CsvRowIssue(1, 'Invalid header row: expected order_id, sku, quantity, price');
            }
        }

        return null;
    }
}
