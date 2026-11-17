<?php

namespace Bale\Tala\Http\Controllers;

use Bale\Tala\Events\TalaAuthenticated;
use Bale\Tala\Models\TalaUser;
use Bale\Tala\Support\TokenStore;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class KeycloakController extends Controller
{
    /** Penanda sesi: warga baru saja logout lokal. */
    private const LOGGED_OUT = 'tala.just_logged_out';

    public function redirect()
    {
        $driver = Socialite::driver('keycloak');

        // Sesi SSO Keycloak bertahan setelah logout lokal. Paksa form login
        // sekali agar warga tidak otomatis masuk kembali.
        if (session()->get(self::LOGGED_OUT)) {
            $driver->with(['prompt' => 'login']);
        }

        return $driver->redirect();
    }

    public function callback(Request $request)
    {
        try {
            $remote = Socialite::driver('keycloak')->user();
        } catch (\Throwable $e) {
            report($e);

            return redirect('/')->with('error', __('Gagal masuk melalui akun citizen. Silakan coba lagi.'));
        }

        $email = $remote->getEmail();

        if (! $email) {
            return redirect('/')->with('error', __('Akun citizen tidak memiliki email.'));
        }

        $user = TalaUser::where('email', $email)->first();

        // Akun internal (admin/root) tidak boleh masuk portal warga.
        if ($user && $user->role !== 'citizen') {
            return redirect('/')->with('error', __('Akun ini terdaftar sebagai pengguna internal. Gunakan akun citizen.'));
        }

        if (! $user) {
            $user = TalaUser::create([
                'name' => $remote->getName() ?: Str::before($email, '@'),
                'username' => $remote->getId() ?: $email,
                'email' => $email,
                'role' => 'citizen',
                'password' => bcrypt(Str::random(32)),
            ]);
        }

        Auth::guard('tala')->login($user, true);
        $request->session()->regenerate();
        $request->session()->forget(self::LOGGED_OUT);

        TokenStore::put($remote);

        // Session note `identity_provider` hanya ada bila login lewat IdP
        // (mis. `google`); login form lokal Keycloak tidak mengirimnya.
        $request->session()->put('tala.login_method', $remote->getRaw()['identity_provider'] ?? null);

        // Package theme menyimpan data domainnya sendiri lewat listener.
        event(new TalaAuthenticated($user, $remote));

        return redirect()->intended(config('tala.home', '/panel'));
    }

    public function logout(Request $request)
    {
        Auth::guard('tala')->logout();
        TokenStore::clear();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Logout lokal saja: end_session Keycloak butuh post_logout_redirect_uri
        // terdaftar di client, dan tanpanya Keycloak menolak redirect balik.
        // shortcut: sesi SSO tetap hidup, nyalakan lagi setelah URI terdaftar.
        $request->session()->put(self::LOGGED_OUT, true);

        return redirect('/');
    }
}
