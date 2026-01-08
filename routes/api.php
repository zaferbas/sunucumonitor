<?php

use App\Http\Controllers\Api\MetricController;
use Illuminate\Support\Facades\Route;

// Health check
Route::get('/health', [MetricController::class, 'health']);

// Metric routes (API key protected)
Route::middleware(['api.key'])->group(function () {
    // Metrik gönderimi
    Route::post('/metrics', [MetricController::class, 'store']);
    
    // Sunucu listesi
    Route::get('/servers', [MetricController::class, 'servers']);
    
    // Sunucu detayı
    Route::get('/servers/{server}', [MetricController::class, 'show']);
    
    // Sunucu metrikleri
    Route::get('/servers/{server}/metrics', [MetricController::class, 'metrics']);
    
    // Kullanıcı bazlı özet
    Route::get('/servers/{server}/users', [MetricController::class, 'users']);
});
