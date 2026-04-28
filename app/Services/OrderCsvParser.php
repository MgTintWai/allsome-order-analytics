<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Structural CSV splitting only: one raw line in → one cell array out via `str_getcsv`.
 * No column counts, no header semantics, no domain validation.
 */
final class OrderCsvParser
{
    /**
     * @param  list<string>  $rawLines
     * @return list<list<string|null>>
     */
    public function toCellRows(array $rawLines): array
    {
        $rows = [];
        foreach ($rawLines as $line) {
            $rows[] = str_getcsv($line);
        }

        return $rows;
    }
}
