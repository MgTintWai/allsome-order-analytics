<?php

namespace App\Validation\Rules;

use App\Support\OrderUploadConstraints;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

/**
 * One valid CSV upload: single part in the field, .csv on disk name + guessed type, declared
 * MIME in allow-list, and size cap. (See README: how duplicate Postman files are caught.)
 */
final class OrderCsvFile implements ValidationRule
{
    public function __construct(
        private readonly Request $request
    ) {}

    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        $file = $this->unwrapExactlyOne($attribute, $this->request->file($attribute), $fail);
        if (! $file instanceof UploadedFile) {
            return;
        }

        if (! $file->isValid()) {
            $fail('The upload is invalid or failed on the server.');

            return;
        }

        if ($this->isOverSize($file)) {
            $fail('The CSV file may not be larger than '.OrderUploadConstraints::maxUploadSizeMegabytes().' MB.');

            return;
        }

        if (! $this->hasAllowedCsvExtension($file)) {
            $fail('Only a file with the .csv extension is allowed. Renamed PDF, image, or text files are not accepted.');

            return;
        }

        if (! $this->hasAllowedCsvMimeType($file)) {
            $fail('The file does not look like a CSV (MIME type is not an allowed CSV type).');

            return;
        }
    }

    private function isOverSize(UploadedFile $file): bool
    {
        return $file->getSize() > OrderUploadConstraints::MAX_UPLOAD_BYTES;
    }

    private function hasAllowedCsvExtension(UploadedFile $file): bool
    {
        $ext = strtolower((string) $file->getClientOriginalExtension());
        $inferred = strtolower((string) $file->guessExtension());

        if ($ext !== OrderUploadConstraints::EXPECTED_CSV_FILE_EXTENSION) {
            return false;
        }

        return $inferred === OrderUploadConstraints::EXPECTED_CSV_FILE_EXTENSION;
    }

    private function hasAllowedCsvMimeType(UploadedFile $file): bool
    {
        $mime = strtolower((string) $file->getMimeType());

        return in_array($mime, OrderUploadConstraints::CSV_ALLOWED_MIMES, true);
    }

    private function unwrapExactlyOne(string $attribute, mixed $files, \Closure $fail): ?UploadedFile
    {
        if ($files === null) {
            $fail('A CSV file is required in the "'.$attribute.'" field.');

            return null;
        }

        if (is_array($files)) {
            if (count($files) === 0) {
                $fail('A CSV file is required in the "'.$attribute.'" field.');

                return null;
            }
            if (count($files) > 1) {
                $fail('Only one file may be attached to the "csv" field. In Postman form-data, add a single file row; remove any second file for the same key.');

                return null;
            }
            $files = $files[0] ?? null;
        }

        if (! $files instanceof UploadedFile) {
            $fail('A valid file upload is required in the "csv" field.');

            return null;
        }

        return $files;
    }
}
