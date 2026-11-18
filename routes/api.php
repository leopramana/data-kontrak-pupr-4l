<?php

use App\Http\Controllers\Api\DatasetApiController;
use Illuminate\Support\Facades\Route;

// API baca untuk dashboard Bupati (K7). Hanya dataset yang sudah disetujui.
// Saat ini terbuka tanpa kunci akses dan dibatasi 60 permintaan/menit per IP;
// keputusan soal kunci akses menunggu rapat (brief, pertanyaan terbuka no. 2).
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/datasets', [DatasetApiController::class, 'index'])->name('api.datasets.index');
    Route::get('/datasets/{slug}', [DatasetApiController::class, 'show'])->name('api.datasets.show');
});
