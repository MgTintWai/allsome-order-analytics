<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Contracts\OrderCsvRepositoryInterface;
use App\DTO\OrderLine;
use App\Services\OrderAnalyticsService;
use PHPUnit\Framework\TestCase;

class OrderAnalyticsServiceTest extends TestCase
{
    public function test_summarizes_revenue_and_best_selling_sku_tiebreaks_by_sku_name(): void
    {
        $orderLines = [
            new OrderLine(orderId: 1, sku: 'SKU-B', quantity: 1, price: 10.0),
            new OrderLine(orderId: 2, sku: 'SKU-A', quantity: 2, price: 5.0),
            new OrderLine(orderId: 3, sku: 'SKU-C', quantity: 1, price: 10.0),
        ];

        $repository = $this->createMock(OrderCsvRepositoryInterface::class);
        $out = (new OrderAnalyticsService($repository))->summarize($orderLines);

        $this->assertSame('30.00', $out['total_revenue']);
        $this->assertSame('SKU-A', $out['best_selling_sku']['sku']);
        $this->assertSame(2, $out['best_selling_sku']['total_quantity']);
    }
}
