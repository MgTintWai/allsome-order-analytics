<?php

namespace App\Validation\Rules;

use App\Support\OrderUploadConstraints;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

/**
 * When the same name="csv" is used twice, PHP may only surface one {@see UploadedFile} while the
 * raw POST still contains another part—Content-Length then dwarfs the one visible file. Runs
 * after {@see OrderCsvFile} so we always have a real upload to compare.
 */
final class OrderCsvRequestBodyHeuristic implements ValidationRule
{
    public function __construct(
        private readonly Request $request
    ) {}

    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        $file = $this->oneUploadedFile($this->request->file($attribute));
        if (! $file instanceof UploadedFile) {
            return;
        }

        if ($this->bodyLooksLargerThanThisFile($file)) {
            $fail('The "csv" field may only include one file. The full request is larger than this file alone (another file was often included but only one part is sent to the server; remove the extra file in form-data, or add only one file row for `csv` in Postman).');
        }
    }

    private function oneUploadedFile(mixed $files): ?UploadedFile
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

    private function bodyLooksLargerThanThisFile(UploadedFile $file): bool
    {
        $contentLength = (int) ($this->request->header('Content-Length') ?? $this->request->server('CONTENT_LENGTH') ?? 0);
        if ($contentLength === 0) {
            return false;
        }

        $fileSize = $file->getSize();
        $tolerance = OrderUploadConstraints::multipartToleranceForVisibleFile($fileSize);

        return $contentLength > $fileSize + $tolerance;
    }
}
