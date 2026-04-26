<?php

use App\Http\Controllers\Api\OrderAnalyticsController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:60,1')->group(function () {
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
        ]);
    });

    Route::post('/orders/analytics', [OrderAnalyticsController::class, 'fromUpload']);
});
