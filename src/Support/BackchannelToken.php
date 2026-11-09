<?php

namespace Bale\Tala\Support;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use stdClass;
use UnexpectedValueException;

/**
 * Verifikasi `logout_token` yang dikirim Keycloak lewat backchannel logout.
 * Spesifikasi: OpenID Connect Back-Channel Logout 1.0.
 */
final class BackchannelToken
{
    public const LOGOUT_EVENT = 'http://schemas.openid.net/event/backchannel-logout';

    public static function verify(string $token): stdClass
    {
        if ($token === '') {
            throw new UnexpectedValueException('logout_token kosong');
        }

        $claims = JWT::decode($token, self::keys());

        if (($claims->iss ?? null) !== self::issuer()) {
            throw new UnexpectedValueException('issuer tidak cocok');
        }

        $audience = $claims->aud ?? [];
        $audience = is_array($audience) ? $audience : [$audience];

        if (! in_array(config('services.keycloak.client_id'), $audience, true)) {
            throw new UnexpectedValueException('audience tidak cocok');
        }

        if (! array_key_exists(self::LOGOUT_EVENT, (array) ($claims->events ?? []))) {
            throw new UnexpectedValueException('bukan token backchannel logout');
        }

        if (isset($claims->nonce)) {
            throw new UnexpectedValueException('logout_token tidak boleh berisi nonce');
        }

        if (empty($claims->sub)) {
            throw new UnexpectedValueException('sub tidak ada');
        }

        // Token yang sama tidak boleh dipakai dua kali.
        if (! empty($claims->jti) && ! Cache::add('tala:logout:'.$claims->jti, true, 300)) {
            throw new UnexpectedValueException('logout_token sudah dipakai');
        }

        return $claims;
    }

    /**
     * @return array<string, Key>
     */
    private static function keys(): array
    {
        $jwks = Cache::remember('tala:keycloak:jwks', 3600, function () {
            $response = Http::timeout(10)->get(self::issuer().'/protocol/openid-connect/certs');

            $response->throw();

            return $response->json();
        });

        return JWK::parseKeySet($jwks, 'RS256');
    }

    private static function issuer(): string
    {
        return rtrim((string) config('services.keycloak.base_url'), '/')
            .'/realms/'.config('services.keycloak.realms');
    }
}
