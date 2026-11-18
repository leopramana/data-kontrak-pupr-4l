<?php

use App\Http\Controllers\Auth\MasukController;
use App\Http\Controllers\Panel\DatasetController;
use App\Http\Controllers\Panel\PemeriksaanController;
use App\Http\Controllers\Panel\PenggunaController;
use App\Http\Controllers\Panel\PratinjauController;
use App\Http\Controllers\Publik\KatalogController;
use Illuminate\Support\Facades\Route;

// ---------- Publik ----------
Route::get('/', [KatalogController::class, 'index'])->name('katalog');
Route::get('/dataset/{slug}', [KatalogController::class, 'show'])->name('katalog.show');
Route::get('/dataset/{slug}/unduh.csv', [KatalogController::class, 'unduh'])->name('katalog.unduh');
Route::view('/tentang', 'publik.tentang')->name('tentang');

// ---------- Masuk / keluar ----------
Route::middleware('guest')->group(function () {
    Route::get('/masuk', [MasukController::class, 'create'])->name('masuk');
    Route::post('/masuk', [MasukController::class, 'store']);
});
Route::post('/keluar', [MasukController::class, 'destroy'])->middleware('auth')->name('keluar');

// ---------- Panel ----------
Route::prefix('panel')->middleware(['auth', 'peran'])->group(function () {
    Route::get('/', function () {
        $user = auth()->user();

        return redirect()->route(match (true) {
            $user->isAdmin() => 'pengguna.index',
            $user->isWalidata() => 'pemeriksaan.index',
            default => 'dataset.index',
        });
    })->name('panel');

    // Dataset (operator mengelola; semua peran panel bisa melihat detail sesuai Policy)
    Route::get('/dataset/{dataset}', [DatasetController::class, 'show'])->whereNumber('dataset')->name('dataset.show');

    Route::middleware('peran:operator')->group(function () {
        Route::get('/dataset', [DatasetController::class, 'index'])->name('dataset.index');
        Route::get('/dataset/unggah', [DatasetController::class, 'create'])->name('dataset.create');
        Route::post('/dataset', [DatasetController::class, 'store'])->name('dataset.store');
        Route::get('/dataset/{dataset}/ubah', [DatasetController::class, 'edit'])->name('dataset.edit');
        Route::put('/dataset/{dataset}', [DatasetController::class, 'update'])->name('dataset.update');
        Route::post('/dataset/{dataset}/ajukan', [DatasetController::class, 'ajukan'])->name('dataset.ajukan');
        Route::delete('/dataset/{dataset}', [DatasetController::class, 'destroy'])->name('dataset.destroy');

        Route::get('/pratinjau', [PratinjauController::class, 'show'])->name('pratinjau.show');
        Route::post('/pratinjau/simpan', [PratinjauController::class, 'simpan'])->name('pratinjau.simpan');
        Route::post('/pratinjau/batal', [PratinjauController::class, 'batal'])->name('pratinjau.batal');
    });

    Route::middleware('peran:walidata')->group(function () {
        Route::get('/pemeriksaan', [PemeriksaanController::class, 'index'])->name('pemeriksaan.index');
        Route::post('/pemeriksaan/{dataset}/setujui', [PemeriksaanController::class, 'setujui'])->name('pemeriksaan.setujui');
        Route::post('/pemeriksaan/{dataset}/kembalikan', [PemeriksaanController::class, 'kembalikan'])->name('pemeriksaan.kembalikan');
        Route::post('/pemeriksaan/{dataset}/tarik', [PemeriksaanController::class, 'tarik'])->name('pemeriksaan.tarik');
    });

    Route::middleware('peran:admin')->group(function () {
        Route::get('/pengguna', [PenggunaController::class, 'index'])->name('pengguna.index');
        Route::get('/pengguna/baru', [PenggunaController::class, 'create'])->name('pengguna.create');
        Route::post('/pengguna', [PenggunaController::class, 'store'])->name('pengguna.store');
        Route::get('/pengguna/{pengguna}/ubah', [PenggunaController::class, 'edit'])->name('pengguna.edit');
        Route::put('/pengguna/{pengguna}', [PenggunaController::class, 'update'])->name('pengguna.update');
    });
});
