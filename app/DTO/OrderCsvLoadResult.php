<?php

declare(strict_types=1);

namespace App\DTO;

/**
 * DTO: outcome of reading an orders CSV — valid lines plus per-row validation issues (no throws for bad rows).
 */
final readonly class OrderCsvLoadResult
{
    /**
     * @param  list<OrderLine>  $orderLines
     * @param  list<CsvRowIssue>  $rowIssues
     */
    public function __construct(
        public array $orderLines,
        public array $rowIssues,
    ) {}
}
