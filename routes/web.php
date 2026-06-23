<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FeederController;

// Jalur Dashboard Utama
Route::get('/', [FeederController::class, 'index'])->name('dashboard');
Route::post('/feed-now', [FeederController::class, 'feedNow'])->name('feed.now');

// Rute manajemen dashboard pakan pintar
Route::get('/', [FeederController::class, 'index'])->name('dashboard');
Route::post('/schedule', [FeederController::class, 'store'])->name('schedule.store');
Route::post('/feed-now', [FeederController::class, 'feedNow'])->name('feed.now');
Route::delete('/schedule/{id}', [FeederController::class, 'destroy'])->name('schedule.destroy');

// Jalur Manajemen Jadwal Pakan
Route::get('/jadwal', [FeederController::class, 'jadwalIndex'])->name('jadwal.index');
Route::post('/schedule', [FeederController::class, 'store'])->name('schedule.store');
Route::patch('/schedule/{id}/toggle', [FeederController::class, 'toggleStatus'])->name('schedule.toggle');
Route::delete('/schedule/{id}', [FeederController::class, 'destroy'])->name('schedule.destroy');
