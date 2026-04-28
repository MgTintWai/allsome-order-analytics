<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\OrderCsvRepositoryInterface;
use App\DTO\OrderCsvLoadResult;
use App\Services\OrderImportService;

/**
 * Thin adapter: the “repository” in this app is still the boundary for “load orders from a path”,
 * but implementation is delegated to {@see OrderImportService} (persistence-style role without DB).
 */
final class OrderCsvRepository implements OrderCsvRepositoryInterface
{
    public function __construct(
        private readonly OrderImportService $orderImportService
    ) {}

    public function loadFromFile(string $absolutePath): OrderCsvLoadResult
    {
        return $this->orderImportService->importFromFile($absolutePath);
    }
}
