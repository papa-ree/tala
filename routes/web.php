<?php

use Bale\Tala\Http\Controllers\BackchannelLogoutController;
use Bale\Tala\Http\Controllers\KeycloakController;
use Bale\Tala\Http\Middleware\EnsureTalaSession;
use Bale\Tala\Livewire\Panel\Profile;
use Bale\Tala\Panel;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])->group(function () {
    // Middleware `auth` default mengarah ke route ini.
    Route::get('/auth', fn () => redirect()->route('tala.redirect'))->name('login');
});

// Dipanggil langsung oleh Keycloak (server-to-server). Tanpa middleware `web`:
// tidak ada session/CSRF, autentikasinya tanda tangan `logout_token`.
// Path mengikuti URL yang didaftarkan di client Keycloak; path lama tetap
// dilayani agar client yang belum diperbarui tidak 404.
Route::post('/logout/auth/backchannel', BackchannelLogoutController::class)
    ->name('tala.backchannel');
Route::post('/logout/kisara/backchannel', BackchannelLogoutController::class);

Route::middleware(['web', 'throttle:20,1'])->group(function () {
    Route::get('/auth/redirect', [KeycloakController::class, 'redirect'])->name('tala.redirect');
    Route::get('/auth/callback', [KeycloakController::class, 'callback'])->name('tala.callback');
});

Route::middleware(['web', 'auth:tala', EnsureTalaSession::class])->prefix('panel')->name('tala.panel.')->group(function () {
    Route::get('/', app(Panel::class)->overview())->name('overview');
    Route::get('/profil', Profile::class)->name('profile');

    // Halaman dari package yang mendaftar lewat Panel::registerPackage().
    foreach (app(Panel::class)->pages() as $page) {
        Route::get('/'.$page['package'].'/'.$page['page'], $page['class'])
            ->name($page['package'].'.'.$page['page']);
    }
});

// Logout tetap boleh walau sesi SSO sudah lewat: tanpa `auth` dan tanpa
// middleware sesi, supaya POST dari tab lama tetap keluar dengan bersih.
Route::middleware(['web'])->prefix('panel')->name('tala.panel.')->group(function () {
    Route::post('/logout', [KeycloakController::class, 'logout'])->name('logout');
});
