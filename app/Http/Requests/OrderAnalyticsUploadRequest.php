<?php

namespace App\Http\Requests;

use App\Http\Responses\ApiErrorResponse;
use App\Validation\Rules\OrderCsvFile;
use App\Validation\Rules\OrderCsvRequestBodyHeuristic;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\UploadedFile;

class OrderAnalyticsUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @see theCsvFile() for the resolved {@see \Illuminate\Http\UploadedFile} passed to the controller.
     * @see \App\Validation\Rules\OrderCsvFile
     * @see \App\Validation\Rules\OrderCsvRequestBodyHeuristic
     */
    public function rules(): array
    {
        return [
            'csv' => [
                'bail',
                'required',
                new OrderCsvFile($this),
                new OrderCsvRequestBodyHeuristic($this),
            ],
        ];
    }

    /**
     * Single uploaded file to analyze (the only part when the client did not turn `csv` into a list).
     */
    public function theCsvFile(): UploadedFile
    {
        $f = $this->file('csv');
        if (is_array($f)) {
            $f = $f[0];
        }

        return $f;
    }

    public function messages(): array
    {
        return [
            'csv.required' => 'A CSV file is required in the "csv" field.',
        ];
    }

    public function attributes(): array
    {
        return [
            'csv' => 'CSV file',
        ];
    }

    /**
     * Return the standard error envelope (same shape as other API errors) when rules fail.
     * Note: 413 (post too large) is still handled before validation when PHP’s limit is lower.
     */
    protected function failedValidation(Validator $validator)
    {
        $response = ApiErrorResponse::make(
            'validation_error',
            'Invalid input',
            $validator->errors()->toArray(),
            422
        );

        throw new HttpResponseException($response);
    }
}
