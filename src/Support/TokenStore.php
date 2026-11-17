<?php

namespace Bale\Tala\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Contracts\User as SocialiteUser;

/**
 * Token OIDC citizen disimpan di session. Dipakai untuk memanggil API yang
 * dilindungi Bearer (mis. master wilayah).
 */
final class TokenStore
{
    private const KEY = 'tala_token';

    public static function put(SocialiteUser $user): void
    {
        $expiresIn = property_exists($user, 'expiresIn') ? $user->expiresIn : null;
        $idToken = property_exists($user, 'accessTokenResponseBody')
            ? ($user->accessTokenResponseBody['id_token'] ?? null)
            : null;
        $sid = self::sidFromIdToken($idToken);

        session([self::KEY => [
            'access' => $user->token ?? null,
            'refresh' => $user->refreshToken ?? null,
            'id_token' => $idToken,
            'sid' => $sid,
            'expires_at' => $expiresIn ? now()->addSeconds((int) $expiresIn)->getTimestamp() : null,
        ]]);

        // Backchannel logout Keycloak hanya menyebut `sid`, bukan sesi Laravel.
        // Petakan supaya logout satu perangkat tidak menghapus sesi perangkat lain.
        if ($sid !== null) {
            Cache::put(self::sidKey($sid), session()->getId(), now()->addMinutes((int) config('session.lifetime', 120)));
        }
    }

    /**
     * Sesi Laravel milik `sid` Keycloak yang di-logout (sekali pakai).
     */
    public static function pullSessionId(string $sid): ?string
    {
        $sessionId = Cache::pull(self::sidKey($sid));

        return is_string($sessionId) ? $sessionId : null;
    }

    public static function accessToken(): ?string
    {
        return session(self::KEY.'.access');
    }

    public static function idToken(): ?string
    {
        return session(self::KEY.'.id_token');
    }

    public static function expiresAt(): ?int
    {
        $expires = session(self::KEY.'.expires_at');

        return $expires ? (int) $expires : null;
    }

    public static function expired(): bool
    {
        $expiresAt = self::expiresAt();

        return $expiresAt !== null && now()->getTimestamp() >= ($expiresAt - 30);
    }

    /**
     * Tukar refresh token. `false` berarti sesi SSO Keycloak sudah tidak ada
     * (aplikasi harus logout); `null` berarti Keycloak tidak bisa dihubungi,
     * jadi sesi jangan diputus hanya karena jaringan.
     */
    public static function refresh(): ?bool
    {
        $refresh = session(self::KEY.'.refresh');

        if (! $refresh) {
            return false;
        }

        try {
            $response = Http::asForm()->timeout(10)->post(self::tokenUrl(), [
                'grant_type' => 'refresh_token',
                'client_id' => config('services.keycloak.client_id'),
                'client_secret' => config('services.keycloak.client_secret'),
                'refresh_token' => $refresh,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }

        $body = $response->json();

        if ($response->clientError()) {
            return false;
        }

        if (! $response->successful() || empty($body['access_token'])) {
            return null;
        }

        session([self::KEY => [
            'access' => $body['access_token'],
            'refresh' => $body['refresh_token'] ?? $refresh,
            'id_token' => $body['id_token'] ?? session(self::KEY.'.id_token'),
            'expires_at' => now()->addSeconds((int) ($body['expires_in'] ?? 300))->getTimestamp(),
        ]]);

        return true;
    }

    public static function clear(): void
    {
        $sid = session(self::KEY.'.sid');

        if (is_string($sid) && $sid !== '') {
            Cache::forget(self::sidKey($sid));
        }

        session()->forget(self::KEY);
    }

    /**
     * Alias IdP dari userinfo (`google`, dll.). `null` = login form lokal
     * Keycloak, `false` = gagal ditentukan (jangan dianggap email).
     */
    public static function identityProvider(): string|false|null
    {
        $token = self::accessToken();

        if (! $token) {
            return false;
        }

        try {
            $response = Http::timeout(10)->withToken($token)->get(
                rtrim((string) config('services.keycloak.base_url'), '/')
                .'/realms/'.config('services.keycloak.realms')
                .'/protocol/openid-connect/userinfo'
            );
        } catch (\Throwable $e) {
            report($e);

            return false;
        }

        if (! $response->successful()) {
            return false;
        }

        return $response->json('identity_provider') ?: null;
    }

    private static function tokenUrl(): string
    {
        return rtrim((string) config('services.keycloak.base_url'), '/')
            .'/realms/'.config('services.keycloak.realms')
            .'/protocol/openid-connect/token';
    }

    private static function sidKey(string $sid): string
    {
        return 'tala:sid:'.$sid;
    }

    /**
     * Klaim `sid` (sesi SSO Keycloak) ada di payload id_token, bukan userinfo.
     */
    private static function sidFromIdToken(?string $idToken): ?string
    {
        if (! is_string($idToken) || $idToken === '') {
            return null;
        }

        $parts = explode('.', $idToken);

        if (count($parts) < 2) {
            return null;
        }

        $json = base64_decode(strtr($parts[1], '-_', '+/'), false);
        $claims = $json === false ? null : json_decode($json, true);

        return is_array($claims) && ! empty($claims['sid']) ? (string) $claims['sid'] : null;
    }
}
