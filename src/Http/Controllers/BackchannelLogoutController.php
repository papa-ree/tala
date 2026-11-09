<?php

namespace Bale\Tala\Http\Controllers;

use Bale\Tala\Support\BackchannelToken;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Dipanggil Keycloak (server-to-server) saat sesi SSO berakhir. Tidak ada
 * user di balik request ini: autentikasinya tanda tangan logout_token.
 */
class BackchannelLogoutController extends Controller
{
    public function __invoke(Request $request)
    {
        try {
            $claims = BackchannelToken::verify((string) $request->input('logout_token'));
        } catch (Throwable $e) {
            // Token tidak sah (termasuk probe bot): jangan spam log.
            return response('invalid logout_token', 400);
        }

        $this->revoke((string) $claims->sub);

        return response('', 200);
    }

    private function revoke(string $subject): void
    {
        // `users.username` diisi `sub` Keycloak saat login pertama, jadi tala
        // tidak perlu membaca tabel milik package lain.
        $uuid = DB::table('users')->where('username', $subject)->value('uuid');

        if (! $uuid) {
            return;
        }

        $userId = DB::table('users')->where('uuid', $uuid)->value('id');

        if ($userId) {
            DB::table(config('session.table', 'sessions'))->where('user_id', $userId)->delete();
        }

        // Tanpa ini, cookie remember-me akan menghidupkan sesi tanpa token OIDC.
        DB::table('users')->where('uuid', $uuid)->update(['remember_token' => null]);
    }
}
