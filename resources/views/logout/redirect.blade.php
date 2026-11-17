<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    {{-- `form-action 'self'` memblokir redirect POST ke Keycloak; navigasi
         lewat meta refresh tidak terkena direktif form. --}}
    <meta http-equiv="refresh" content="0; url={{ $url }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>{{ __('Keluar') }} — {{ config('app.name') }}</title>
</head>

<body>
    <p>
        {{ __('Anda telah keluar. Mengalihkan ke halaman keluar SSO…') }}
        <a href="{{ $url }}">{{ __('Lanjutkan') }}</a>
    </p>
</body>

</html>
