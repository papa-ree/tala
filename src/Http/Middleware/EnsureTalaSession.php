<?php

namespace Bale\Tala\Http\Middleware;

use Bale\Tala\Support\TokenStore;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sesi aplikasi mengikuti sesi SSO. Setiap navigasi halaman panel menukar
 * refresh token: bila Keycloak menolak (sesi SSO sudah berakhir), user ikut
 * logout. Keycloak yang sedang tidak bisa dihubungi tidak memutus sesi.
 *
 * Login tanpa token OIDC juga diputus: bisa terjadi saat cookie remember-me
 * menghidupkan ulang sesi yang sudah dicabut lewat backchannel logout.
 *
 * Request Livewire tidak melewati middleware ini; 401 di tengah form
 * ditangani lewat refresh di WilayahClient.
 */
class EnsureTalaSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = TokenStore::accessToken();

        if ($token === null || TokenStore::refresh() === false) {
            Auth::guard('tala')->logout();
            TokenStore::clear();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('tala.redirect')
                ->with('error', __('Sesi Anda berakhir. Silakan masuk lagi.'));
        }

        // Lengkapi sesi lama yang login sebelum fitur metode login ada.
        if (! $request->session()->exists('tala.login_method')) {
            $provider = TokenStore::identityProvider();

            if ($provider !== false) {
                $request->session()->put('tala.login_method', $provider);
            }
        }

        return $next($request);
    }
}
