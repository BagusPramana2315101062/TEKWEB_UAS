<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\ReportController;

// API auth endpoints for demo: login returns a Sanctum personal access token
Route::post('/login', [AuthController::class, 'login'])->name('api.login');
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout'])->name('api.logout');

Route::post('/transactions', [TransactionController::class, 'store'])->middleware('auth:sanctum')->name('api.transactions.store');

// Admin-only transaction status update (supports session auth)
// Admin-only transaction status update (Sanctum token-based API auth)
Route::middleware(['auth:sanctum','role:admin'])->patch('/transactions/{id}/status', [TransactionController::class, 'updateStatus'])->name('api.transactions.updateStatus');

// Admin-only reporting endpoints
Route::middleware(['auth:sanctum','role:admin'])->prefix('reports')->name('api.reports.')->group(function () {
    Route::get('transactions-monthly', [ReportController::class, 'transactionsMonthly'])->name('transactions-monthly');
    Route::get('category-share', [ReportController::class, 'categoryShare'])->name('category-share');
});

// admin summary
Route::middleware(['auth:sanctum','role:admin'])->get('/admin/summary', [ReportController::class, 'summary'])->name('api.admin.summary');

// Admin Tax Setting
use App\Http\Controllers\Api\TaxSettingController;
use App\Http\Controllers\Api\ActivityLogController;
Route::middleware(['auth:sanctum','role:admin'])->prefix('admin')->group(function () {
    Route::get('tax-setting', [TaxSettingController::class, 'show'])->name('api.admin.tax-setting.show');
    Route::put('tax-setting', [TaxSettingController::class, 'update'])->name('api.admin.tax-setting.update');

    // API: activity logs (JSON)
    Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('api.admin.activity-logs.index');
});

// Comments endpoints have been removed for this UAS project (feature disabled).
