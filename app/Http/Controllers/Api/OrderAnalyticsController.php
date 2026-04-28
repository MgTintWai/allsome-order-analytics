<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Concerns\RespondsWithApiErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\OrderAnalyticsUploadRequest;
use App\Http\Responses\OrderAnalyticsSuccessResponse;
use App\Services\OrderAnalyticsService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class OrderAnalyticsController extends Controller
{
    use RespondsWithApiErrors;

    public function __construct(
        private readonly OrderAnalyticsService $orderAnalyticsService
    ) {}

    /**
     * Accept a single CSV upload on field {@see OrderAnalyticsUploadRequest} `csv` and
     * return order analytics as JSON. Validation runs before this action; domain errors
     * are handled by {@see \App\Exceptions\Handler}.
     */
    public function fromUpload(OrderAnalyticsUploadRequest $request): JsonResponse|Response
    {
        $uploadedFile = $request->theCsvFile();
        $realPath = $uploadedFile->getRealPath();
        if ($realPath === false || $realPath === '') {
            return $this->apiError('invalid_upload', 'Unable to read uploaded file.', null, 400);
        }

        $analytics = $this->orderAnalyticsService->analyzeFromCsvPath($realPath);

        return OrderAnalyticsSuccessResponse::make($analytics);
    }
}
