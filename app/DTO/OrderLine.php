<?php

declare(strict_types=1);

namespace App\DTO;

/**
 * DTO: one validated order row from the interview CSV (order_id, sku, quantity, price).
 */
final readonly class OrderLine
{
    /**
     * @param  positive-int  $orderId
     * @param  non-empty-string  $sku
     * @param  positive-int  $quantity
     */
    public function __construct(
        public int $orderId,
        public string $sku,
        public int $quantity,
        public float $price,
    ) {}
}