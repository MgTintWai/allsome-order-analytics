<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\CsvFileReaderInterface;
use App\DTO\CsvRowIssue;
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
                rowIssues: [new CsvRowIssue(1, 'CSV is empty')],
            );
        }

        $cellRows = $this->orderCsvParser->toCellRows($rawLines);

        $headerIssue = $this->orderHeaderValidator->validateHeaderOrIssue($cellRows[0] ?? []);
        if ($headerIssue !== null) {
            return new OrderCsvLoadResult(
                orderLines: [],
                rowIssues: [$headerIssue],
            );
        }

        $orderLines = [];
        $rowIssues = [];
        for ($i = 1, $lineCount = count($rawLines); $i < $lineCount; $i++) {
            $lineNumber = $i + 1;
            if (trim($rawLines[$i]) === '') {
                continue;
            }

            $row = $cellRows[$i] ?? [];
            if ($this->isRowVisiblyEmpty($row)) {
                continue;
            }

            $outcome = $this->orderRowValidator->validateDataRowOrIssue($row, $lineNumber);
            if ($outcome instanceof CsvRowIssue) {
                $rowIssues[] = $outcome;
            } else {
                $orderLines[] = $outcome;
            }
        }

        return new OrderCsvLoadResult($orderLines, $rowIssues);
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
