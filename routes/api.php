<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ServicesController;
use App\Http\Controllers\Api\ServiceRequestController;
use App\Http\Controllers\Api\ServicePhotoController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\CustomerController;


Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/api/spk', [SpkController::class, 'index']);
    Route::get('/services', [ServicesController::class, 'index']);
    Route::get('/profile', [ProfileController::class, 'me']);

  //  Route::get('/services/{service}', [ServicesController::class, 'show']);
    Route::get('/services/{id}', [ServiceController::class, 'show']);
    Route::post('/service-photos', [ServicePhotoController::class, 'store']);
    Route::get('/service-photos/{service}', [ServicePhotoController::class, 'index']);
    Route::get('/service-requests', [ServiceRequestController::class, 'index']);
    Route::get('/service-requests/{id}', [ServiceRequestController::class, 'show']);
    Route::post('/service-requests/{id}', [ServiceRequestController::class, 'update']);

    Route::get('/damages', [ServiceRequestController::class, 'damages']);

    Route::get('/customers', [CustomerController::class, 'index']);
    Route::get('/customers/{id}/vehicles', [CustomerController::class, 'vehicles']);
    Route::delete('/service-requests/{id}', [ServiceRequestController::class, 'destroy']);


    Route::post('/service-requests', [ServiceRequestController::class, 'store']);
});
