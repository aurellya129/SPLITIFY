<?php
use App\Http\Controllers\PatunganController;
use Illuminate\Support\Facades\Route;
Route::get('/', [PatunganController::class, 'create'])->name('patungan.create');
Route::post('/patungan', [PatunganController::class, 'store'])->name('patungan.store');
Route::get('/p/{slug}', [PatunganController::class, 'show'])->name('patungan.show');