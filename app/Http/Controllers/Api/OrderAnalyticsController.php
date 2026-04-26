<?php

namespace App\Http\Controllers\Api;

use App\Http\Concerns\RespondsWithApiErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\OrderAnalyticsUploadRequest;
use App\Services\OrderAnalyticsService;
use Illuminate\Http\JsonResponse;

class OrderAnalyticsController extends Controller
{
    use RespondsWithApiErrors;

    public function __construct(
        private readonly OrderAnalyticsService $orderAnalyticsService
    ) {}

    /**
     * Multipart "csv": one .csv file (typed MIME + size rules in OrderCsvFile). Success: JSON analytics.
     * Errors: {@see \App\Exceptions\Handler} and {@see \App\Http\Requests\OrderAnalyticsUploadRequest::failedValidation}.
     */
    public function fromUpload(OrderAnalyticsUploadRequest $request): JsonResponse
    {
        $path = $request->theCsvFile()->getRealPath() ?? '';
        if ($path === '' || $path === false) {
            return $this->apiError('invalid_upload', 'Unable to read uploaded file.', null, 400);
        }

        $payload = $this->orderAnalyticsService->analyzeFromCsvPath($path);

        return response()->json($payload);
    }
}
