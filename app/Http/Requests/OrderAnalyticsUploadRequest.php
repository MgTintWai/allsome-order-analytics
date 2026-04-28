<?php

declare(strict_types=1);

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
    /**
     * Public upload endpoint: no auth required for the assessment use case.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<int|string|OrderCsvFile|OrderCsvRequestBodyHeuristic>>
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
     * Single uploaded part for `csv` (the first part when the client sent an array; validation rejects multiple).
     */
    public function theCsvFile(): UploadedFile
    {
        $fileOrList = $this->file('csv');
        if (is_array($fileOrList)) {
            $fileOrList = $fileOrList[0];
        }

        return $fileOrList;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'csv.required' => 'A CSV file is required in the "csv" field.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'csv' => 'CSV file',
        ];
    }

    /**
     * Return the same JSON `error` envelope as other API errors when input validation fails.
     *
     * @throws HttpResponseException
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
