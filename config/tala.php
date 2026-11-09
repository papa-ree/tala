<?php

return [
    // Matikan seluruh login SSO + panel: route tidak didaftarkan.
    'enabled' => env('TALA_ENABLED', true),

    'guard' => 'tala',

    // Halaman tujuan setelah login berhasil.
    'home' => '/panel',

    // Platform manajemen akun eksternal (mirip account.google.com).
    // Tombol "Kelola Akun" disembunyikan selama nilainya kosong.
    'account_url' => env('TALA_ACCOUNT_URL'),
];
