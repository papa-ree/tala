<?php

namespace Bale\Tala\Events;

use Bale\Tala\Models\TalaUser;
use Laravel\Socialite\Contracts\User as SocialiteUser;

/**
 * Dipancarkan setelah login Keycloak berhasil. Package theme mendengarkannya
 * untuk menyimpan data domainnya sendiri; tala tidak tahu tabel mereka.
 */
class TalaAuthenticated
{
    public function __construct(
        public TalaUser $user,
        public SocialiteUser $remote,
    ) {}
}
