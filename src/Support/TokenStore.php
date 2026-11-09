<?php

namespace Bale\Tala\Support;

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

        session([self::KEY => [
            'access' => $user->token ?? null,
            'refresh' => $user->refreshToken ?? null,
            'id_token' => property_exists($user, 'accessTokenResponseBody')
                ? ($user->accessTokenResponseBody['id_token'] ?? null)
                : null,
            'expires_at' => $expiresIn ? now()->addSeconds((int) $expiresIn)->getTimestamp() : null,
        ]]);
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
}
