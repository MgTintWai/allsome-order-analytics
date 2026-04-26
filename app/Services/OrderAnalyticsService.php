<?php

namespace App\Services;

use App\Contracts\OrderCsvRepositoryInterface;
use App\Support\OrderUploadConstraints;
use App\Exceptions\NoValidOrderRowsInCsvException;
use App\Exceptions\OrderCsvFileException;

class OrderAnalyticsService
{
    public function __construct(
        private readonly OrderCsvRepositoryInterface $orderCsvRepository
    ) {}

    /**
     * End-to-end: load order lines from a CSV on disk, then compute analytics.
     * Repository = data access (file CSV here, same role as a DB-backed repository in other services).
     *
     * @throws OrderCsvFileException
     * @throws NoValidOrderRowsInCsvException
     * @return array{total_revenue: float, best_selling_sku: array{sku: string, total_quantity: int}, warnings?: list<array{line: int, message: string}>}
     */
    public function analyzeFromCsvPath(string $absolutePath): array
    {
        $load = $this->orderCsvRepository->loadFromFile($absolutePath);
        if ($load['lines'] === []) {
            throw new NoValidOrderRowsInCsvException($load['row_errors']);
        }

        $summary = $this->summarize($load['lines']);
        if ($load['row_errors'] !== []) {
            $summary['warnings'] = $load['row_errors'];
        }

        return $summary;
    }

    /**
     * @param  list<array{order_id: int, sku: string, quantity: int, price: float}>  $lines
     * @return array{total_revenue: float, best_selling_sku: array{sku: string, total_quantity: int}}
     */
    public function summarize(array $lines): array
    {
        if ($lines === []) {
            throw new \InvalidArgumentException('No order lines to summarize');
        }

        $totalRevenue = 0.0;
        $quantitiesBySku = [];

        foreach ($lines as $line) {
            $totalRevenue += $line['quantity'] * $line['price'];
            $key = $line['sku'];
            $quantitiesBySku[$key] = ($quantitiesBySku[$key] ?? 0) + $line['quantity'];
        }

        $best = $this->bestSellingSku($quantitiesBySku);

        return [
            'total_revenue' => round($totalRevenue, OrderUploadConstraints::JSON_REVENUE_DECIMALS),
            'best_selling_sku' => [
                'sku' => $best['sku'],
                'total_quantity' => $best['total_quantity'],
            ],
        ];
    }

    /**
     * @param  array<string, int>  $quantitiesBySku
     * @return array{sku: string, total_quantity: int}
     */
    private function bestSellingSku(array $quantitiesBySku): array
    {
        if ($quantitiesBySku === []) {
            throw new \InvalidArgumentException('No SKU quantities to compare');
        }

        arsort($quantitiesBySku, SORT_NUMERIC);
        $maxQuantity = (int) reset($quantitiesBySku);
        $candidates = array_keys(array_filter(
            $quantitiesBySku,
            static fn (int $q) => $q === $maxQuantity
        ));
        sort($candidates, SORT_STRING);
        $sku = (string) $candidates[0];

        return ['sku' => $sku, 'total_quantity' => $maxQuantity];
    }
}
