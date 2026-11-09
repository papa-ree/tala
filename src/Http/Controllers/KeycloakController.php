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
    public function redirect()
    {
        return Socialite::driver('keycloak')->redirect();
    }

    public function callback(Request $request)
    {
        try {
            $remote = Socialite::driver('keycloak')->user();
        } catch (\Throwable $e) {
            report($e);

            return redirect('/')->with('error', __('Gagal masuk melalui Keycloak. Silakan coba lagi.'));
        }

        $email = $remote->getEmail();

        if (! $email) {
            return redirect('/')->with('error', __('Akun Keycloak tidak memiliki email.'));
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
        // Akhiri sesi SSO Keycloak juga, lalu kembali ke aplikasi.
        $logoutUrl = Socialite::driver('keycloak')->getLogoutUrl(
            url('/'),
            config('services.keycloak.client_id'),
            TokenStore::idToken(),
        );

        Auth::guard('tala')->logout();
        TokenStore::clear();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->away($logoutUrl);
    }
}
