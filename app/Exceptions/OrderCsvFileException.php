<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * The orders CSV file path is missing, not a file, or cannot be read into memory.
 */
class OrderCsvFileException extends RuntimeException
{
}
