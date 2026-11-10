<?php

namespace Bale\Tala;

use Bale\Tala\Attributes\PanelPage;
use Bale\Tala\Commands\InstallTala;
use Bale\Umpak\Concerns\HasLivewireComponents;
use Composer\InstalledVersions;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Livewire\Component as LivewireComponent;
use Livewire\Livewire;
use ReflectionClass;
use SocialiteProviders\Keycloak\Provider as KeycloakProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;
use Symfony\Component\Finder\Finder;

class TalaServiceProvider extends ServiceProvider
{
    use HasLivewireComponents;

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/tala.php', 'tala');

        $this->app->singleton(Panel::class);

        $this->app->bind('command.tala:install', InstallTala::class);
        $this->commands(['command.tala:install']);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'tala');

        // Driver `keycloak` tidak auto-register; manager hanya menyiarkan event.
        $this->app['events']->listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite('keycloak', KeycloakProvider::class);
        });

        $this->discoverPanelPages($this->app->make(Panel::class));

        $this->discoverLivewireComponents(
            __DIR__.'/Livewire',
            'Bale\\Tala\\Livewire',
            'tala'
        );

        $this->app->booted(function () {
            if (! config('tala.enabled')) {
                // Route ber-`auth:tala` mengarahkan tamu ke nama route `login`.
                // Tanpa ini Laravel melempar RouteNotFoundException (500).
                Route::middleware('web')->get('/auth', fn () => redirect('/'))->name('login');

                return;
            }

            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/tala.php' => config_path('tala.php'),
            ], 'tala-config');
        }
    }

    /**
     * Halaman panel hanya diambil dari package yang sudah mendaftar lewat
     * Panel::registerPackage(). Package tanpa registrasi diabaikan.
     */
    protected function discoverPanelPages(Panel $panel): void
    {
        foreach (InstalledVersions::getInstalledPackages() as $package) {
            if (! Str::startsWith($package, 'bale/') || $package === 'bale/tala') {
                continue;
            }

            $path = InstalledVersions::getInstallPath($package);

            if ($path === null) {
                continue;
            }

            $slug = Str::after(basename($path), 'bale-');
            $directory = $path.'/src/Livewire/Panel';
            $namespace = $this->psr4SrcNamespace($path);

            if (! $panel->hasPackage($slug) || $namespace === null || ! is_dir($directory)) {
                continue;
            }

            $finder = (new Finder)->files()->in($directory)->name('*.php')->sortByName();

            foreach ($finder as $file) {
                $class = $namespace.'\\Livewire\\Panel\\'
                    .str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname());

                if (! class_exists($class) || ! is_subclass_of($class, LivewireComponent::class)) {
                    continue;
                }

                $reflection = new ReflectionClass($class);

                // Overview hanya bisa dipakai lewat Panel::registerOverview().
                if (Str::endsWith($class, '\\Overview')) {
                    continue;
                }

                $attributes = $reflection->getAttributes(PanelPage::class);

                if ($attributes === []) {
                    continue;
                }

                /** @var PanelPage $meta */
                $meta = $attributes[0]->newInstance();

                $page = Str::kebab($reflection->getShortName());

                Livewire::component('panel.'.$slug.'.'.$page, $class);

                $panel->addPage([
                    'package' => $slug,
                    'page' => $page,
                    'label' => $meta->label,
                    'icon' => $meta->icon,
                    'order' => $meta->order,
                    'class' => $class,
                ]);
            }
        }
    }

    /**
     * Namespace PSR-4 yang dipetakan ke direktori `src/` milik package.
     */
    protected function psr4SrcNamespace(string $path): ?string
    {
        $composer = $path.'/composer.json';

        if (! is_file($composer)) {
            return null;
        }

        $json = json_decode((string) file_get_contents($composer), true);

        foreach ($json['autoload']['psr-4'] ?? [] as $namespace => $dir) {
            if (rtrim((string) $dir, '/') === 'src') {
                return rtrim($namespace, '\\');
            }
        }

        return null;
    }
}
