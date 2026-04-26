<?php

namespace Tests\Unit;

use App\Contracts\OrderCsvRepositoryInterface;
use App\Services\OrderAnalyticsService;
use PHPUnit\Framework\TestCase;

class OrderAnalyticsServiceTest extends TestCase
{
    public function test_summarizes_revenue_and_best_selling_sku_tiebreaks_by_sku_name(): void
    {
        $lines = [
            ['order_id' => 1, 'sku' => 'SKU-B', 'quantity' => 1, 'price' => 10.0],
            ['order_id' => 2, 'sku' => 'SKU-A', 'quantity' => 2, 'price' => 5.0],
            ['order_id' => 3, 'sku' => 'SKU-C', 'quantity' => 1, 'price' => 10.0],
        ];

        $repo = $this->createMock(OrderCsvRepositoryInterface::class);
        $out = (new OrderAnalyticsService($repo))->summarize($lines);

        $this->assertSame(30.0, $out['total_revenue']);
        $this->assertSame('SKU-A', $out['best_selling_sku']['sku']);
        $this->assertSame(2, $out['best_selling_sku']['total_quantity']);
    }
}
