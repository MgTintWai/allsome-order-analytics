<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\Support\OrderUploadConstraints;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

/**
 * If the same `name="csv"` is sent twice, PHP can expose a single {@see UploadedFile} while
 * the raw multipart body is still as large as both parts. Compares `Content-Length` to the
 * visible file size (with tolerance). Runs after {@see OrderCsvFile} so a real file exists.
 */
final class OrderCsvRequestBodyHeuristic implements ValidationRule
{
    public function __construct(
        private readonly Request $request
    ) {}

    /**
     * @param  \Closure(string): void  $fail
     */
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        $file = $this->soleUploadedFile($this->request->file($attribute));
        if (! $file instanceof UploadedFile) {
            return;
        }

        if ($this->requestBodyExceedsFilePlusTolerance($file)) {
            $fail('The "csv" field may only include one file. The full request is larger than this file alone (another file was often included but only one part is sent to the server; remove the extra file in form-data, or add only one file row for `csv` in Postman).');
        }
    }

    private function soleUploadedFile(mixed $files): ?UploadedFile
    {
        if ($files === null) {
            return null;
        }
        if (is_array($files)) {
            if (count($files) !== 1) {
                return null;
            }
            $files = $files[0];
        }

        return $files instanceof UploadedFile ? $files : null;
    }

    private function requestBodyExceedsFilePlusTolerance(UploadedFile $file): bool
    {
        $contentLength = (int) ($this->request->header('Content-Length') ?? $this->request->server('CONTENT_LENGTH') ?? 0);
        if ($contentLength === 0) {
            return false;
        }

        $fileSizeBytes = $file->getSize();
        $toleranceBytes = OrderUploadConstraints::multipartToleranceForVisibleFile($fileSizeBytes);

        return $contentLength > $fileSizeBytes + $toleranceBytes;
    }
}
