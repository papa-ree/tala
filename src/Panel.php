<?php

namespace Bale\Tala;

use Bale\Tala\Livewire\Panel\Overview;
use LogicException;

/**
 * Registry panel warga. Package theme mendaftar di register():
 *
 *   $this->app->resolving(Panel::class, fn (Panel $panel) => $panel->registerPackage('disnaker', 'Dinas Tenaga Kerja'));
 *
 * Hanya package terdaftar yang halaman `src/Livewire/Panel`-nya dipakai.
 */
class Panel
{
    /** @var array<string, array{slug: string, name: string}> */
    protected array $packages = [];

    /** @var array<int, string> */
    protected array $profileSections = [];

    protected ?string $overview = null;

    /** @var array<int, array{package: string, page: string, label: string, icon: ?string, order: int, class: string}> */
    protected array $pages = [];

    public function registerPackage(string $slug, string $name): void
    {
        $this->packages[$slug] = ['slug' => $slug, 'name' => $name];
    }

    public function registerOverview(string $class): void
    {
        if ($this->overview !== null && $this->overview !== $class) {
            throw new LogicException("Overview panel sudah dipakai {$this->overview}; {$class} tidak boleh menimpanya.");
        }

        $this->overview = $class;
    }

    public function registerProfileSection(string $class): void
    {
        $this->profileSections[] = $class;
    }

    /** @return array<string, array{slug: string, name: string}> */
    public function packages(): array
    {
        return $this->packages;
    }

    public function hasPackage(string $slug): bool
    {
        return isset($this->packages[$slug]);
    }

    public function overview(): string
    {
        return $this->overview ?? Overview::class;
    }

    /** @return array<int, string> */
    public function profileSections(): array
    {
        return $this->profileSections;
    }

    /**
     * @param  array{package: string, page: string, label: string, icon: ?string, order: int, class: string}  $page
     */
    public function addPage(array $page): void
    {
        $this->pages[] = $page;
    }

    /** @return array<int, array{package: string, page: string, label: string, icon: ?string, order: int, class: string}> */
    public function pages(): array
    {
        $pages = $this->pages;

        usort($pages, fn (array $a, array $b) => $a['order'] <=> $b['order']);

        return $pages;
    }
}
