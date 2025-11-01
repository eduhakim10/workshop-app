<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\QuotationPrintController;
use App\Http\Controllers\ServiceRequestController;


Route::get('/', function () {
    return redirect(route('filament.admin.auth.login'));
});
Route::get('/quotation/{service}/print-overview', [QuotationPrintController::class, 'overview'])->name('quotation.print.overview');
Route::get('/quotation/{service}/print-detail', [QuotationPrintController::class, 'detail'])->name('quotation.print.detail');
Route::get('/service-requests/{id}', [ServiceRequestController::class, 'show'])->name('service-requests.show');

Route::get('/service-requests/{id}/after', [ServiceRequestController::class, 'after'])->name('service-requests.after');