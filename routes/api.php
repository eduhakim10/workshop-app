<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ServicesController;
use App\Http\Controllers\Api\ServicePhotoController;
use App\Http\Controllers\Api\ServiceRequestController;
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/api/spk', [SpkController::class, 'index']);
    Route::get('/services', [ServicesController::class, 'index']);
    Route::get('/services/{service}', [ServicesController::class, 'show']);
    Route::post('/service-photos', [ServicePhotoController::class, 'store']);
    Route::get('/service-photos/{service}', [ServicePhotoController::class, 'index']);
    Route::post('/service-requests', [ServiceRequestController::class, 'store']);
});
