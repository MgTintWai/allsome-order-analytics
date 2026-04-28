<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\CsvRowError;
use App\DTO\OrderLine;
use App\Support\OrderUploadConstraints;

/**
 * Domain validation for a single data row (after header). Messages match the legacy repository.
 */
final class OrderRowValidator
{
    /**
     * @param  list<string|null>  $row
     */
    public function validateDataRowOrError(array $row, int $lineNumber): OrderLine|CsvRowError
    {
        if (count($row) < OrderUploadConstraints::EXPECTED_CSV_COLUMN_COUNT) {
            $expectedColumnCount = OrderUploadConstraints::EXPECTED_CSV_COLUMN_COUNT;

            return new CsvRowError(
                $lineNumber,
                "Not enough columns (expected {$expectedColumnCount})"
            );
        }

        $orderIdRaw = trim((string) $row[0]);
        if ($orderIdRaw === '' || ! ctype_digit($orderIdRaw)) {
            return new CsvRowError($lineNumber, 'order_id must be a positive integer');
        }

        $orderId = (int) $orderIdRaw;
        if ($orderId < 1) {
            return new CsvRowError($lineNumber, 'order_id must be a positive integer');
        }

        $sku = trim((string) $row[1]);
        if ($sku === '') {
            return new CsvRowError($lineNumber, 'sku is required');
        }

        $quantityRaw = trim((string) $row[2]);
        if ($quantityRaw === '' || ! ctype_digit($quantityRaw)) {
            return new CsvRowError($lineNumber, 'quantity must be a positive integer');
        }

        $quantity = (int) $quantityRaw;
        if ($quantity < 1) {
            return new CsvRowError($lineNumber, 'quantity must be a positive integer');
        }

        $priceRaw = trim((string) $row[3]);
        if ($priceRaw === '' || ! is_numeric($priceRaw)) {
            return new CsvRowError($lineNumber, 'price must be a non-negative number');
        }

        $price = (float) $priceRaw;
        if ($price < 0 || is_nan($price) || is_infinite($price)) {
            return new CsvRowError($lineNumber, 'price must be a non-negative number');
        }

        return new OrderLine(
            orderId: $orderId,
            sku: $sku,
            quantity: $quantity,
            price: $price
        );
    }
}
