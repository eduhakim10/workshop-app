<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ServicesController;
use App\Http\Controllers\Api\ServiceRequestController;
use App\Http\Controllers\Api\ServicePhotoController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\Customer\CustomerAuthController;
use App\Http\Controllers\Api\Customer\DashboardController as CustomerDashboardController;
use App\Http\Controllers\Api\Customer\QuotationController as CustomerQuotationController;
use App\Http\Controllers\Api\Customer\ServiceController as CustomerServiceController;


Route::post('/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| Customer Portal API (consumed by the customer_portal Filament app)
|--------------------------------------------------------------------------
| Auth: Sanctum tokens issued to CustomerUser accounts. All data is scoped
| to the authenticated user's customer_id.
*/
Route::prefix('customer')->group(function () {
    Route::post('/login', [CustomerAuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [CustomerAuthController::class, 'me']);
        Route::post('/logout', [CustomerAuthController::class, 'logout']);

        Route::get('/dashboard', [CustomerDashboardController::class, 'index']);

        Route::get('/services/{id}/photos', [CustomerServiceController::class, 'photos']);

        Route::get('/quotations', [CustomerQuotationController::class, 'index']);
        Route::get('/quotations/{id}', [CustomerQuotationController::class, 'show']);
        Route::get('/quotations/{id}/print', [CustomerQuotationController::class, 'print']);
        Route::post('/quotations/{id}/upload-po', [CustomerQuotationController::class, 'uploadPo']);

        Route::get('/users', [CustomerUserController::class, 'index']);
        Route::post('/users', [CustomerUserController::class, 'store']);
        Route::put('/users/{id}', [CustomerUserController::class, 'update']);
        Route::delete('/users/{id}', [CustomerUserController::class, 'destroy']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/api/spk', [SpkController::class, 'index']);
    Route::get('/services', [ServicesController::class, 'index']);
    Route::get('/profile', [ProfileController::class, 'me']);

  //  Route::get('/services/{service}', [ServicesController::class, 'show']);
    Route::get('/services/{id}', [ServicesController::class, 'show']);
    Route::post('/services/{id}', [ServicesController::class, 'updateAfter']);
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
