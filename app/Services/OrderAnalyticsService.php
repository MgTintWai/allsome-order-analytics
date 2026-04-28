<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\OrderCsvRepositoryInterface;
use App\DTO\CsvRowError;
use App\DTO\OrderLine;
use App\Exceptions\NoValidOrderRowsInCsvException;
use App\Exceptions\OrderCsvFileException;
use App\Support\OrderUploadConstraints;
use InvalidArgumentException;

class OrderAnalyticsService
{
    public function __construct(
        private readonly OrderCsvRepositoryInterface $orderCsvRepository
    ) {}

    /**
     * Load order lines from a CSV on disk, then compute revenue and best-selling SKU.
     * The repository models data access; analytics stay in this service.
     *
     * @return array{
     *     total_revenue: string,
     *     best_selling_sku: array{sku: string, total_quantity: int},
     *     warnings?: list<array{line: int, message: string}>
     * }
     *
     * @throws OrderCsvFileException When the file cannot be read.
     * @throws NoValidOrderRowsInCsvException When the CSV has no valid order rows to aggregate.
     */
    public function analyzeFromCsvPath(string $absolutePath): array
    {
        $csvLoad = $this->orderCsvRepository->loadFromFile($absolutePath);
        if ($csvLoad->orderLines === []) {
            throw new NoValidOrderRowsInCsvException(
                $this->rowErrorPayloads($csvLoad->rowErrors)
            );
        }

        $analytics = $this->summarize($csvLoad->orderLines);
        if ($csvLoad->rowErrors !== []) {
            $analytics['warnings'] = $this->rowErrorPayloads($csvLoad->rowErrors);
        }

        return $analytics;
    }

    /**
     * Aggregate pre-validated order lines into total revenue and best-selling SKU (by total quantity).
     *
     * @param  list<OrderLine>  $orderLines
     * @return array{total_revenue: string, best_selling_sku: array{sku: string, total_quantity: int}}
     *
     * @throws InvalidArgumentException If {@see $orderLines} is empty.
     */
    public function summarize(array $orderLines): array
    {
        if ($orderLines === []) {
            throw new InvalidArgumentException('No order lines to summarize.');
        }

        $totalRevenue = 0.0;
        /** @var array<string, int> $quantityBySku */
        $quantityBySku = [];

        foreach ($orderLines as $orderLine) {
            $totalRevenue += $orderLine->quantity * $orderLine->price;
            $skuKey = $orderLine->sku;
            $quantityBySku[$skuKey] = ($quantityBySku[$skuKey] ?? 0) + $orderLine->quantity;
        }

        $bestSku = $this->resolveBestSellingSku($quantityBySku);

        return [
            'total_revenue' => OrderUploadConstraints::formatTotalRevenueForJson($totalRevenue),
            'best_selling_sku' => [
                'sku' => $bestSku['sku'],
                'total_quantity' => $bestSku['total_quantity'],
            ],
        ];
    }

    /**
     * @param  list<CsvRowError>  $rowErrors
     * @return list<array{line: int, message: string}>
     */
    private function rowErrorPayloads(array $rowErrors): array
    {
        return array_map(
            static fn (CsvRowError $rowError): array => $rowError->toArray(),
            $rowErrors
        );
    }

    /**
     * Picks the SKU with the largest total quantity; on a tie, the lexicographically smallest SKU.
     *
     * @param  array<string, int>  $quantityBySku
     * @return array{sku: string, total_quantity: int}
     *
     * @throws InvalidArgumentException If {@see $quantityBySku} is empty.
     */
    private function resolveBestSellingSku(array $quantityBySku): array
    {
        if ($quantityBySku === []) {
            throw new InvalidArgumentException('No SKU quantities to compare.');
        }

        arsort($quantityBySku, SORT_NUMERIC);
        $highestTotalQuantity = (int) reset($quantityBySku);
        $tiedSkus = array_keys(array_filter(
            $quantityBySku,
            static fn (int $totalQuantity): bool => $totalQuantity === $highestTotalQuantity
        ));
        sort($tiedSkus, SORT_STRING);
        $winningSku = (string) $tiedSkus[0];

        return ['sku' => $winningSku, 'total_quantity' => $highestTotalQuantity];
    }
}
