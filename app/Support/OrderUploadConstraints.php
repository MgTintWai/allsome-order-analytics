<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Product limits and tuning for order-CSV upload and JSON output (revenue decimal places, etc.).
 */
final class OrderUploadConstraints
{
    public const MAX_UPLOAD_KILOBYTES = 4096;

    public const KILOBYTE = 1024;

    public const MAX_UPLOAD_BYTES = self::MAX_UPLOAD_KILOBYTES * self::KILOBYTE;

    public const EXPECTED_CSV_FILE_EXTENSION = 'csv';

    /**
     * Declared MIME types accepted for a real CSV. Extension must still be .csv: clients
     * cannot trust MIME alone, and extension alone is spoofable; both reduce risk.
     * text/plain: many stacks report .csv that way; still disallowed for anything but .csv name.
     */
    public const CSV_ALLOWED_MIMES = [
        'text/csv',
        'application/csv',
        'text/x-comma-separated-values',
        'text/x-csv',
        'text/plain',
    ];

    public const JSON_REVENUE_DECIMALS = 2;

    public const EXPECTED_CSV_COLUMN_COUNT = 4;

    public const MULTIPART_BODY_TOLERANCE_FLOOR = 12 * 1024;

    public const MULTIPART_BODY_TOLERANCE_CAP = 512 * 1024;

    public const MULTIPART_BODY_SIZE_RATIO = 0.02;

    /**
     * User-facing app limit, derived from {@see self::MAX_UPLOAD_KILOBYTES} (e.g. 4 MB for 4096 KiB).
     */
    public static function maxUploadSizeMegabytes(): int
    {
        return (int) (self::MAX_UPLOAD_KILOBYTES / self::KILOBYTE);
    }

    /**
     * Format total revenue for JSON with exactly two fractional digits (e.g. "710.00").
     * Encoding a float with json_encode often drops trailing zeros (710), unlike brief examples (610.00).
     *
     * @return non-empty-string
     */
    public static function formatTotalRevenueForJson(float $totalRevenue): string
    {
        return number_format($totalRevenue, self::JSON_REVENUE_DECIMALS, '.', '');
    }

    /**
     * Slack for multipart boundary fields when comparing the visible upload size to `Content-Length`.
     *
     * @param  int  $visibleFileSizeBytes  Size of the single {@see \Illuminate\Http\UploadedFile} PHP kept.
     */
    public static function multipartToleranceForVisibleFile(int $visibleFileSizeBytes): int
    {
        return max(
            self::MULTIPART_BODY_TOLERANCE_FLOOR,
            min(
                self::MULTIPART_BODY_TOLERANCE_CAP,
                (int) ($visibleFileSizeBytes * self::MULTIPART_BODY_SIZE_RATIO)
            )
        );
    }
}
