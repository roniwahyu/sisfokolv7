<?php

use App\Modules\Finance\Controllers\ItemPembayaranController;
use App\Modules\Finance\Controllers\TagihanSiswaController;
use App\Modules\Finance\Controllers\PembayaranController;
use App\Modules\Finance\Controllers\TabunganSiswaController;
use App\Modules\Finance\Controllers\LaporanKeuanganController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('finance')->name('finance.')->group(function () {
    // Master Item Pembayaran
    Route::resource('item-pembayaran', ItemPembayaranController::class);

    // Tagihan Siswa
    Route::get('tagihan', [TagihanSiswaController::class, 'index'])->name('tagihan.index');
    Route::get('tagihan/generate', [TagihanSiswaController::class, 'create'])->name('tagihan.create');
    Route::post('tagihan/generate', [TagihanSiswaController::class, 'generate'])->name('tagihan.generate');

    // Kasir & Pembayaran
    Route::get('pembayaran', [PembayaranController::class, 'index'])->name('pembayaran.index');
    Route::post('pembayaran/{siswa}/bayar', [PembayaranController::class, 'store'])->name('pembayaran.store');
    Route::get('pembayaran/riwayat', [PembayaranController::class, 'riwayat'])->name('pembayaran.riwayat');
    Route::get('pembayaran/kwitansi/{pembayaran}', [PembayaranController::class, 'cetakKwitansi'])->name('pembayaran.kwitansi');

    // Tabungan Siswa
    Route::get('tabungan', [TabunganSiswaController::class, 'index'])->name('tabungan.index');
    Route::get('tabungan/create', [TabunganSiswaController::class, 'create'])->name('tabungan.create');
    Route::post('tabungan', [TabunganSiswaController::class, 'store'])->name('tabungan.store');
    Route::get('tabungan/{tabungan}', [TabunganSiswaController::class, 'show'])->name('tabungan.show');
    Route::post('tabungan/{tabungan}/setor', [TabunganSiswaController::class, 'setor'])->name('tabungan.setor');
    Route::post('tabungan/{tabungan}/tarik', [TabunganSiswaController::class, 'tarik'])->name('tabungan.tarik');

    // Laporan Keuangan
    Route::get('laporan', [LaporanKeuanganController::class, 'index'])->name('laporan.index');
});
