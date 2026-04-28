<?php

declare(strict_types=1);

namespace App\DTO;

/**
 * DTO: outcome of reading an orders CSV — valid lines plus per-row issues (no throws for bad rows).
 */
final readonly class OrderCsvLoadResult
{
    /**
     * @param  list<OrderLine>  $orderLines
     * @param  list<CsvRowError>  $rowErrors
     */
    public function __construct(
        public array $orderLines,
        public array $rowErrors,
    ) {}
}