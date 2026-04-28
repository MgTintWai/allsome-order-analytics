<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\Support\OrderUploadConstraints;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

/**
 * Ensures a single part in the field: valid upload, under max size, `.csv` by client and guessed
 * extension, and declared MIME in the allow list. Duplicates in Postman are also rejected when PHP
 * exposes an array of uploads. See also {@see OrderCsvRequestBodyHeuristic} for the one-visible-file case.
 */
final class OrderCsvFile implements ValidationRule
{
    public function __construct(
        private readonly Request $request
    ) {}

    /**
     * @param  \Closure(string): void  $fail
     */
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        $uploaded = $this->unwrapSingleUploadedFile($attribute, $this->request->file($attribute), $fail);
        if (! $uploaded instanceof UploadedFile) {
            return;
        }

        if (! $uploaded->isValid()) {
            $fail('The upload is invalid or failed on the server.');

            return;
        }

        if ($this->isExceedingMaxSize($uploaded)) {
            $fail('The CSV file may not be larger than '.OrderUploadConstraints::maxUploadSizeMegabytes().' MB.');

            return;
        }

        if (! $this->hasAllowedCsvExtension($uploaded)) {
            $fail('Only a file with the .csv extension is allowed. Renamed PDF, image, or text files are not accepted.');

            return;
        }

        if (! $this->hasAllowedCsvMimeType($uploaded)) {
            $fail('The file does not look like a CSV (MIME type is not an allowed CSV type).');

            return;
        }
    }

    private function isExceedingMaxSize(UploadedFile $file): bool
    {
        return $file->getSize() > OrderUploadConstraints::MAX_UPLOAD_BYTES;
    }

    private function hasAllowedCsvExtension(UploadedFile $file): bool
    {
        $clientExtension = strtolower((string) $file->getClientOriginalExtension());
        $inferredExtension = strtolower((string) $file->guessExtension());

        if ($clientExtension !== OrderUploadConstraints::EXPECTED_CSV_FILE_EXTENSION) {
            return false;
        }

        return $inferredExtension === OrderUploadConstraints::EXPECTED_CSV_FILE_EXTENSION;
    }

    private function hasAllowedCsvMimeType(UploadedFile $file): bool
    {
        $declaredMime = strtolower((string) $file->getMimeType());

        return in_array($declaredMime, OrderUploadConstraints::CSV_ALLOWED_MIMES, true);
    }

    /**
     * @param  \Closure(string): void  $fail
     */
    private function unwrapSingleUploadedFile(string $attribute, mixed $files, \Closure $fail): ?UploadedFile
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
