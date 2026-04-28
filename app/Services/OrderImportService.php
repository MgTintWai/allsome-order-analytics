<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\CsvFileReaderInterface;
use App\DTO\CsvRowError;
use App\DTO\OrderCsvLoadResult;
use App\Exceptions\OrderCsvFileException;

/**
 * Application orchestration: read file → split CSV → validate header → validate each data row.
 * Produces the same {@see OrderCsvLoadResult} as the former monolithic {@see \App\Repositories\OrderCsvRepository}.
 */
final class OrderImportService
{
    public function __construct(
        private readonly CsvFileReaderInterface $csvFileReader,
        private readonly OrderCsvParser $orderCsvParser,
        private readonly OrderHeaderValidator $orderHeaderValidator,
        private readonly OrderRowValidator $orderRowValidator,
    ) {}

    /**
     * @throws OrderCsvFileException From {@see CsvFileReaderInterface::readRawLines}
     */
    public function importFromFile(string $absolutePath): OrderCsvLoadResult
    {
        $rawLines = $this->csvFileReader->readRawLines($absolutePath);

        if ($rawLines === []) {
            return new OrderCsvLoadResult(
                orderLines: [],
                rowErrors: [new CsvRowError(1, 'CSV is empty')],
            );
        }

        $cellRows = $this->orderCsvParser->toCellRows($rawLines);

        $headerError = $this->orderHeaderValidator->validateHeaderOrError($cellRows[0] ?? []);
        if ($headerError !== null) {
            return new OrderCsvLoadResult(
                orderLines: [],
                rowErrors: [$headerError],
            );
        }

        $orderLines = [];
        $rowErrors = [];
        for ($i = 1, $lineCount = count($rawLines); $i < $lineCount; $i++) {
            $lineNumber = $i + 1;
            if (trim($rawLines[$i]) === '') {
                continue;
            }

            $row = $cellRows[$i] ?? [];
            if ($this->isRowVisiblyEmpty($row)) {
                continue;
            }

            $outcome = $this->orderRowValidator->validateDataRowOrError($row, $lineNumber);
            if ($outcome instanceof CsvRowError) {
                $rowErrors[] = $outcome;
            } else {
                $orderLines[] = $outcome;
            }
        }

        return new OrderCsvLoadResult($orderLines, $rowErrors);
    }

    /**
     * @param  list<string|null>  $row
     */
    private function isRowVisiblyEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if ($cell !== null && trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }
}
