# Bale Tala

Autentikasi SSO Keycloak dan panel warga global untuk ekosistem Bale.

Tala menyediakan dua hal:

1. **Login SSO** lewat Keycloak (Socialite + `socialiteproviders/keycloak`), termasuk
   logout backchannel server-to-server.
2. **Panel warga** (`/panel`) beserta primitif tampilannya: shell sidebar + drawer,
   mode terang/gelap/otomatis, kartu, badge, tombol, dan komponen form.

Package fitur (mis. `bale-disnaker`) tidak membuat panel sendiri; ia menumpang
panel Tala dan mendaftarkan halamannya.

## Instalasi

```bash
composer require bale/tala
php artisan tala:install
```

`tala:install` saat ini hanya memastikan package terbaca; publish konfigurasi
dilakukan manual bila perlu:

```bash
php artisan vendor:publish --tag=tala-config
```

Kredensial Keycloak diisi di `config/services.php` milik aplikasi
(`keycloak.client_id`, `client_secret`, `redirect`, dan URL realm).

## Konfigurasi

`config/tala.php`:

| Key | Arti |
|---|---|
| `guard` | Nama guard auth untuk warga (default `tala`). |
| `home` | Halaman tujuan setelah login berhasil (default `/panel`). |
| `account_url` | URL platform manajemen akun eksternal. Tombol "Kelola Akun" disembunyikan selama kosong. |

## Route

| Method | Path | Nama |
|---|---|---|
| GET | `/login` | `login` (redirect ke Keycloak) |
| GET | `/login/keycloak/redirect` | `tala.redirect` |
| GET | `/login/keycloak/callback` | `tala.callback` |
| POST | `/logout/kisara/backchannel` | `tala.backchannel` |
| GET | `/panel` | `tala.panel.overview` |
| GET | `/panel/profil` | `tala.panel.profile` |
| POST | `/panel/logout` | `tala.panel.logout` |

Halaman milik package terdaftar muncul sebagai `/panel/{package}/{page}`.

## Mendaftarkan Halaman Panel

Package fitur mendaftar lewat singleton `Panel` pada `register()` milik
service provider-nya:

```php
use Bale\Tala\Panel;

$this->app->resolving(Panel::class, function (Panel $panel) {
    $panel->registerPackage('disnaker', 'Dinas Tenaga Kerja');
});
```

Halaman diambil otomatis dari `src/Livewire/Panel` package tersebut, tetapi
hanya kelas yang memakai atribut `#[PanelPage]` yang masuk menu:

```php
use Bale\Tala\Attributes\PanelPage;

#[PanelPage(label: 'Status', icon: 'inbox', order: 10)]
class Status extends Component
{
    //
}
```

Tiga hook lain pada `Panel`:

- `registerOverview(string $class)` — ganti halaman `/panel` untuk tenant
  tersebut. Hanya satu yang boleh; pendaftar kedua melempar `LogicException`.
- `registerProfileSection(string $class)` — tambah blok di halaman profil.
- `addPage(array $page)` — dipakai internal oleh auto-discovery.

Kelas bernama `Overview` sengaja dilewati auto-discovery; overview hanya bisa
dipakai lewat `registerOverview()`.

## Komponen Blade

Semua view memakai namespace `tala`:

```blade
<x-tala::panel.page-header :title="__('Pelatihan')" :subtitle="__('Ringkasan.')" />
<x-tala::panel.card title="Status" icon="inbox">...</x-tala::panel.card>
<x-tala::panel.stat title="Total" :data="42" icon="inbox" />
<x-tala::panel.badge tone="info" dot>{{ __('Aktif') }}</x-tala::panel.badge>
<x-tala::panel.button>...</x-tala::panel.button>
<x-tala::panel.select wire:model="district">...</x-tala::panel.select>
<x-tala::panel.empty-state icon="inbox" title="Belum ada data." />
```

Daftar komponen: `badge`, `button`, `card`, `empty-state`, `page-header`,
`select`, `stat`.

Tema dibaca dari `localStorage.hs_theme` (`light`, `dark`, `auto`) sehingga
pilihan pengguna tetap konsisten antar halaman.

## Struktur

```
config/tala.php
resources/views/components/panel/   komponen panel
resources/views/layouts/panel.blade.php
routes/web.php
src/
├── Attributes/PanelPage.php
├── Commands/InstallTala.php
├── Http/Controllers/               Keycloak + backchannel logout
├── Http/Middleware/EnsureTalaSession.php
├── Livewire/Panel/                 Overview, Profile
├── Models/TalaUser.php
├── Support/                        penyimpanan token
└── Panel.php                       registry halaman panel
```
