<?php

namespace Bale\Tala\Http\Controllers;

use Bale\Tala\Support\BackchannelToken;
use Bale\Tala\Support\TokenStore;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use stdClass;
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

        $this->revoke($claims);

        return response('', 200);
    }

    private function revoke(stdClass $claims): void
    {
        // Keycloak mengakhiri satu sesi browser; `sid` menunjuk sesi itu saja.
        // Menghapus semua sesi user membuat login di perangkat lain ikut
        // terlempar ke halaman depan.
        $sid = (string) ($claims->sid ?? '');

        if ($sid !== '') {
            $sessionId = TokenStore::pullSessionId($sid);

            if ($sessionId !== null) {
                DB::table(config('session.table', 'sessions'))->where('id', $sessionId)->delete();
            }

            return;
        }

        // Tanpa `sid` artinya seluruh sesi user berakhir (spesifikasi).
        // `users.username` diisi `sub` Keycloak saat login pertama.
        $user = DB::table('users')->where('username', (string) $claims->sub)->first(['id', 'uuid']);

        if (! $user) {
            return;
        }

        DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();

        // Tanpa ini, cookie remember-me akan menghidupkan sesi tanpa token OIDC.
        DB::table('users')->where('uuid', $user->uuid)->update(['remember_token' => null]);
    }
}
