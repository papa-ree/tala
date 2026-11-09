<?php

namespace Bale\Tala\Models;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * User portal warga. Tabel `users` dipakai bersama dengan admin, jadi
 * `users.id` tetap bigint auto-increment: kolom `uuid` terpisah.
 */
class TalaUser extends User
{
    protected $table = 'users';

    protected $fillable = [
        'name',
        'username',
        'email',
        'role',
        'password',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid7();
            }
        });
    }
}
