<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/servers/{server}', [DashboardController::class, 'show'])->name('servers.show');
Route::get('/servers/{server}/chart-data', [DashboardController::class, 'chartData'])->name('servers.chart-data');
