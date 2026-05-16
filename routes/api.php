<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FunnelController;
use App\Http\Controllers\Api\AttritionController;
use App\Http\Controllers\Api\RetentionController;
use App\Http\Controllers\Api\InsightController;
use App\Http\Controllers\Api\TrafficController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\EnrollmentController;

// Public routes — tidak perlu token
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

// Protected routes — wajib pakai token
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::get('/me', [AuthController::class, 'me']);
    });

    // Dashboard
    Route::prefix('dashboard')->group(function () {
        Route::get('/', [DashboardController::class, 'index']);
        Route::get('/sessions', [DashboardController::class, 'sessions']);
        Route::get('/trend', [DashboardController::class, 'trend']);
    });

    // Funnel
    Route::prefix('funnel')->group(function () {
        Route::get('/', [FunnelController::class, 'index']);
        Route::get('/stage', [FunnelController::class, 'stageDetail']);
        Route::get('/comparison', [FunnelController::class, 'comparison']);
    });

    // Attrition
    Route::prefix('attrition')->group(function () {
        Route::get('/', [AttritionController::class, 'index']);
        Route::get('/stage', [AttritionController::class, 'stageDetail']);
        Route::get('/comparison', [AttritionController::class, 'comparison']);
        Route::get('/reasons', [AttritionController::class, 'reasons']);
        Route::get('/heatmap', [AttritionController::class, 'heatmap']);
    });

    // Retention
    Route::prefix('retention')->group(function () {
        Route::get('/', [RetentionController::class, 'index']);
        Route::get('/students', [RetentionController::class, 'students']);
        Route::get('/comparison', [RetentionController::class, 'comparison']);
        Route::get('/by-program', [RetentionController::class, 'byProgram']);
        Route::get('/by-faculty', [RetentionController::class, 'byFaculty']);
        Route::get('/trend', [RetentionController::class, 'trend']);
    });

    // Insights
    Route::prefix('insights')->group(function () {
        Route::get('/', [InsightController::class, 'index']);
        Route::get('/type', [InsightController::class, 'byType']);
        Route::get('/all', [InsightController::class, 'all']);
    });

    // Traffic
    Route::prefix('traffic')->group(function () {
        Route::get('/', [TrafficController::class, 'index']);
        Route::get('/by-category', [TrafficController::class, 'byCategory']);
        Route::get('/source', [TrafficController::class, 'sourceDetail']);
        Route::get('/sources', [TrafficController::class, 'sources']);
    });

    // Reports
    Route::prefix('reports')->group(function () {
        Route::get('/', [ReportController::class, 'index']);
        Route::get('/export/pdf', [ReportController::class, 'exportPdf']);
        Route::get('/export/xlsx', [ReportController::class, 'exportXlsx']);
        Route::get('/export/csv', [ReportController::class, 'exportCsv']);
    });

    // Enrollments
    Route::prefix('enrollments')->group(function () {
        Route::get('/pending', [EnrollmentController::class, 'pending']);
    });
});