<?php

namespace App\Support;

/**
 * Product limits and tuning values for order-CSV upload + analytics.
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

    public static function maxUploadSizeMegabytes(): int
    {
        return (int) (self::MAX_UPLOAD_KILOBYTES / self::KILOBYTE);
    }

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
