<?php

use Illuminate\Support\Facades\Route;

// --- Controllers ---
use App\Http\Controllers\{
    AuthController,
    DashboardController,
    AcaraController,
    PegawaiController,
    PesertaController,
    PresensiController,
    PresensiQrController,
    PresensiOnlineController,
    PengaturanController,
    LaporanController,
    NotificationController
};

/*
|--------------------------------------------------------------------------
| Web Routes - SIPRES
|--------------------------------------------------------------------------
*/

// ========================================================================
// 1. PUBLIC ROUTES
// ========================================================================

Route::get('/', function () { return view('layouts.landing.index'); });

Route::controller(PresensiQrController::class)->group(function () {
    Route::get('/qr/absen/{acara}/{token}', 'viewQr')->name('qr.view');
    Route::get('/qr/img/{acara}/{token}', 'streamQr')->name('qr.image');
    Route::get('/qr/img/{acara}/{token}.png', 'streamQr');
});

Route::controller(PresensiOnlineController::class)->group(function () {
    Route::get('/presensi/online/{acara}', 'showForm')->name('presensi.online.form');
    Route::post('/presensi/online/submit', 'submit')->name('presensi.online.submit');
});

Route::controller(AuthController::class)->group(function () {
    Route::get('/login', 'index')->name('login');
    Route::post('/login', 'login')->name('login.perform');
    Route::post('/logout', 'logout')->name('logout');
    Route::get('/forgot-password', 'forgot')->name('forgot-password');
    Route::post('/forgot-password', 'sendResetLink')->name('password.send');
    Route::get('/reset-password/{token}', 'showResetForm')->name('password.reset');
    Route::post('/reset-password', 'resetPassword')->name('password.update');
});

// ========================================================================
// 2. PROTECTED ADMIN ROUTES
// ========================================================================

Route::middleware(['auth'])->prefix('admin')->group(function () {

    // --- DASHBOARD ---
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // --- ACARA ---
    Route::controller(AcaraController::class)->group(function () {
        Route::get('/acara', 'index')->name('acara');
        Route::get('/acara/data', 'data')->name('acara.data');
        Route::post('/acara', 'store')->name('acara.store');
        Route::put('/acara/{acara}', 'update')->name('acara.update');
        Route::delete('/acara/{acara}', 'destroy')->name('acara.destroy');
        Route::get('/acara/presensi/{acara}', 'presensiAcara')->name('acara.presensi');
        Route::put('/acara/{acara}/tolerance', 'updateTolerance');
    });

    // --- PEGAWAI ---
    Route::controller(PegawaiController::class)->group(function () {
        Route::get('/pegawai', 'index')->name('pegawai');
        Route::get('/pegawai/data', 'data')->name('pegawai.data');
        Route::post('/pegawai', 'store')->name('pegawai.store');
        Route::put('/pegawai/{id}', 'update')->name('pegawai.update');
        Route::delete('/pegawai/{id}', 'destroy')->name('pegawai.destroy');
        Route::post('/pegawai/import', 'import')->name('pegawai.import');
        Route::get('/pegawai/find/{nip}', 'findByNip')->name('pegawai.find');
        Route::get('/pegawai/all-json', 'getAllJson'); 
    });

    // --- PESERTA ---
    Route::controller(PesertaController::class)->group(function () {
        Route::get('/peserta', 'index')->name('peserta');
        Route::get('/peserta/view/{id}', 'viewPeserta')->name('view-peserta');
        Route::get('/peserta/acara', 'acaraList')->name('peserta.acara');
        Route::get('/peserta/event/{acara}', 'eventDetail')->name('peserta.event');
        Route::get('/peserta/data/{acara}', 'pesertaByAcara')->name('peserta.data');
        Route::get('/peserta/stats/{acara}', 'stats')->name('peserta.stats');
        Route::get('/peserta/{id}/simple-list', 'simpleList')->name('peserta.simple-list');
        Route::post('/peserta', 'store')->name('peserta.store');
        Route::post('/peserta/bulk-store', 'storeBulk')->name('peserta.bulk-store');
        Route::put('/peserta/{peserta}', 'update')->name('peserta.update');
        Route::delete('/peserta/{peserta}', 'destroy')->name('peserta.destroy');
        Route::post('/peserta/import/{acara}', 'import')->name('peserta.import');
        Route::get('/peserta/export/{acara}','export')->name('peserta.export');
        Route::get('/peserta/print-qr/{id_acara}', 'downloadQrPdf')->name('peserta.print-qr');
        Route::get('/peserta/send-qr/{acara}', 'sendQr')->name('peserta.send-qr');
        Route::post('/peserta/send-qr/{acara}/whatsapp', [PresensiQrController::class, 'sendWhatsApp']);
        Route::post('/peserta/send-qr/{acara}/email', [PresensiQrController::class, 'sendEmail']);
        Route::post('/peserta/send-qr/{eventId}/{method}/{nip}', [PresensiQrController::class, 'sendQrSingle']);
        Route::get('/peserta/history/{id_acara}', 'history')->name('peserta.history');
    });

    // --- PRESENSI ---
    Route::controller(PresensiController::class)->group(function () {
        Route::get('/presensi', 'index')->name('presensi');
        Route::get('/presensi/view/{id}', 'viewPresensi')->name('view-presensi');
        Route::get('/presensi/data/{acara}', 'data')->name('presensi.data');
        Route::get('/presensi/stats/{acara}', 'stats')->name('presensi.stats');
        Route::get('/presensi/lookup/{id_presensi}', 'lookup')->name('presensi.lookup');
        Route::get('/presensi/export-document/{acara}', 'exportDocument')->name('presensi.export');
        Route::post('/presensi/confirm', [PresensiQrController::class, 'confirmAttendance'])->name('presensi.confirm');
    });

    // --- LAPORAN (PERBAIKAN UTAMA DI SINI) ---
    Route::controller(LaporanController::class)->group(function () {
        Route::get('/laporan', 'index')->name('laporan');
        Route::get('/laporan/view/{id}', 'viewLaporan')->name('laporan.view');
        
        // API Data
        Route::get('/laporan/event-info/{id}', 'getEventInfo')->name('laporan.event-info');
        Route::get('/laporan/data/{id}', 'getData')->name('laporan.data');
        Route::get('/laporan/stats/{id}', 'getStats')->name('laporan.stats');
        
        // Export Excel Saja (PDF Dihapus)
        Route::get('/laporan/export/excel/{id}', 'exportExcel')->name('laporan.export.laporan');
    });

    // --- NOTIFIKASI ---
    Route::controller(NotificationController::class)->group(function () {
        Route::get('/notifications', 'index')->name('notifications.index');
        Route::post('/notifications/read-all', 'markAllRead')->name('notifications.readAll');
        Route::get('/notifications/history', 'history')->name('notifications.history');
    });

    // --- PENGATURAN ---
    Route::controller(PengaturanController::class)->group(function () {
        Route::get('/pengaturan', 'index')->name('pengaturan');
        Route::post('/pengaturan/nama', 'updateName')->name('pengaturan.update.name');
        Route::post('/pengaturan/email', 'updateEmail')->name('pengaturan.update.email');
        Route::post('/pengaturan/password', 'updatePassword')->name('pengaturan.update.password');
    });

});