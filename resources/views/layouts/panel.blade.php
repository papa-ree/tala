<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Panel Warga') }} — {{ config('app.name') }}</title>

    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    {{-- Terapkan tema sebelum paint supaya tidak berkedip. --}}
    <script>
        window.talaThemeLabels = @json(['light' => __('Terang'), 'dark' => __('Gelap'), 'auto' => __('Otomatis')]);

        (() => {
            const media = window.matchMedia('(prefers-color-scheme: dark)');
            const apply = (mode) => {
                const dark = mode === 'dark' || (mode === 'auto' && media.matches);
                document.documentElement.classList.toggle('dark', dark);
            };

            apply(localStorage.getItem('hs_theme') || 'auto');
            media.addEventListener('change', () => apply(localStorage.getItem('hs_theme') || 'auto'));
        })();
    </script>

    <style>
        html.dark {
            color-scheme: dark;
        }
    </style>
</head>

<body
    class="min-h-screen bg-stone-50 font-[Inter,system-ui,sans-serif] text-stone-900 antialiased dark:bg-stone-950 dark:text-stone-100">

    <a href="#panel-content"
        class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-xl focus:bg-accent-600 focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white">
        {{ __('Lewati ke konten') }}
    </a>

    @php
        $panel = app(\Bale\Tala\Panel::class);
        $packages = $panel->packages();
        $pagesByPackage = collect($panel->pages())->groupBy('package');
        $accountUrl = config('tala.account_url');
        $user = auth('tala')->user();
        $initial = mb_strtoupper(mb_substr($user?->name ?? __('W'), 0, 1));

        $currentPage = collect($panel->pages())->first(
            fn (array $page) => request()->routeIs('tala.panel.'.$page['package'].'.'.$page['page'])
        );
        $pageTitle = request()->routeIs('tala.panel.profile')
            ? __('Profil')
            : ($currentPage ? __($currentPage['label']) : __('Overview'));

        $navItem = 'relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-500/40';
        $navActive = 'bg-accent-50 text-accent-700 dark:bg-accent-500/10 dark:text-accent-300';
        $navIdle = 'text-stone-600 hover:bg-stone-100 hover:text-stone-900 dark:text-stone-400 dark:hover:bg-white/[0.05] dark:hover:text-stone-100';
        $iconButton = 'inline-flex size-9 items-center justify-center rounded-xl border border-stone-200/80 bg-white text-stone-500 transition hover:bg-stone-100 hover:text-stone-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-500/40 dark:border-white/10 dark:bg-white/[0.04] dark:text-stone-400 dark:hover:bg-white/[0.08] dark:hover:text-white';
    @endphp

    <div x-data="{ sidebar: false }" x-on:keydown.escape.window="sidebar = false"
        x-on:livewire:navigated.window="sidebar = false" class="min-h-screen lg:flex">

        {{-- Sidebar --}}
        <aside id="panel-sidebar"
            class="fixed inset-y-0 left-0 z-40 flex w-72 -translate-x-full flex-col border-r border-stone-200/80 bg-white transition-transform duration-200 ease-out dark:border-white/[0.08] dark:bg-stone-900 lg:sticky lg:top-0 lg:h-screen lg:translate-x-0"
            x-bind:class="sidebar ? 'translate-x-0 shadow-2xl' : ''">
            <a href="{{ route('tala.panel.overview') }}" wire:navigate
                class="flex items-center gap-3 border-b border-stone-100 px-5 py-5 dark:border-white/[0.06]">
                <span
                    class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-stone-900 text-white dark:bg-white dark:text-stone-900">
                    <x-lucide-user-round class="size-5" />
                </span>
                <span class="min-w-0">
                    <span class="block truncate text-sm font-semibold tracking-tight text-stone-900 dark:text-white">
                        {{ __('Panel Warga') }}
                    </span>
                    <span class="block truncate text-[11px] text-stone-500 dark:text-stone-400">
                        {{ config('app.name') }}
                    </span>
                </span>
            </a>

            <nav class="flex-1 overflow-y-auto px-3 py-4" aria-label="{{ __('Navigasi panel') }}">
                @php $overviewActive = request()->routeIs('tala.panel.overview'); @endphp

                <a href="{{ route('tala.panel.overview') }}" wire:navigate
                    @if ($overviewActive) aria-current="page" @endif
                    class="{{ $navItem }} {{ $overviewActive ? $navActive : $navIdle }}">
                    @if ($overviewActive)
                        <span class="absolute inset-y-2 left-0 w-0.5 rounded-full bg-accent-500"></span>
                    @endif
                    <x-lucide-layout-dashboard class="size-4 shrink-0" />
                    {{ __('Overview') }}
                </a>

                @foreach ($packages as $slug => $package)
                    @if (($pagesByPackage[$slug] ?? collect())->isNotEmpty())
                        <p
                            class="px-3 pb-2 pt-6 text-[10px] font-semibold uppercase tracking-[0.12em] text-stone-400 dark:text-stone-500">
                            {{ __($package['name']) }}
                        </p>

                        @foreach ($pagesByPackage[$slug] as $page)
                            @php
                                $pageActive = request()->routeIs('tala.panel.'.$slug.'.'.$page['page']);
                            @endphp

                            <a href="{{ route('tala.panel.'.$slug.'.'.$page['page']) }}" wire:navigate
                                @if ($pageActive) aria-current="page" @endif
                                class="{{ $navItem }} {{ $pageActive ? $navActive : $navIdle }}">
                                @if ($pageActive)
                                    <span class="absolute inset-y-2 left-0 w-0.5 rounded-full bg-accent-500"></span>
                                @endif
                                <x-dynamic-component :component="'lucide-'.($page['icon'] ?? 'file-text')"
                                    class="size-4 shrink-0" />
                                {{ __($page['label']) }}
                            </a>
                        @endforeach
                    @endif
                @endforeach

                <p
                    class="px-3 pb-2 pt-6 text-[10px] font-semibold uppercase tracking-[0.12em] text-stone-400 dark:text-stone-500">
                    {{ __('Akun') }}
                </p>

                @php $profileActive = request()->routeIs('tala.panel.profile'); @endphp

                <a href="{{ route('tala.panel.profile') }}" wire:navigate
                    @if ($profileActive) aria-current="page" @endif
                    class="{{ $navItem }} {{ $profileActive ? $navActive : $navIdle }}">
                    @if ($profileActive)
                        <span class="absolute inset-y-2 left-0 w-0.5 rounded-full bg-accent-500"></span>
                    @endif
                    <x-lucide-user-round class="size-4 shrink-0" />
                    {{ __('Profil') }}
                </a>

                @if ($accountUrl)
                    <a href="{{ $accountUrl }}" target="_blank" rel="noopener"
                        class="{{ $navItem }} {{ $navIdle }}">
                        <x-lucide-settings class="size-4 shrink-0" />
                        {{ __('Kelola Akun') }}
                        <x-lucide-arrow-up-right class="ml-auto size-3.5 opacity-60" />
                    </a>
                @endif
            </nav>

            <div class="border-t border-stone-100 p-3 dark:border-white/[0.06]">
                <form method="POST" action="{{ route('tala.panel.logout') }}">
                    @csrf
                    <button type="submit"
                        class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-rose-600 transition-colors hover:bg-rose-50 hover:text-rose-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-500/40 dark:text-rose-400 dark:hover:bg-rose-500/10 dark:hover:text-rose-300">
                        <x-lucide-log-out class="size-4 shrink-0" />
                        {{ __('Keluar') }}
                    </button>
                </form>
            </div>
        </aside>

        {{-- Overlay mobile --}}
        <div class="fixed inset-0 z-30 bg-stone-950/40 backdrop-blur-sm lg:hidden" x-show="sidebar" x-cloak
            x-transition.opacity x-on:click="sidebar = false"></div>

        {{-- Konten --}}
        <div class="flex min-w-0 flex-1 flex-col">

            <header
                class="sticky top-0 z-20 flex items-center gap-3 border-b border-stone-200/70 bg-stone-50/80 px-4 py-3 backdrop-blur-md dark:border-white/[0.08] dark:bg-stone-950/80 sm:px-6 lg:px-8">
                <button type="button" x-on:click="sidebar = true" x-bind:aria-expanded="sidebar"
                    aria-controls="panel-sidebar" aria-label="{{ __('Buka navigasi') }}"
                    class="{{ $iconButton }} lg:hidden">
                    <x-lucide-menu class="size-4" />
                </button>

                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-stone-900 dark:text-white">{{ $pageTitle }}</p>
                    <p class="truncate text-[11px] text-stone-500 dark:text-stone-400">{{ __('Panel Warga') }}</p>
                </div>

                {{-- Toggle tema --}}
                <div x-data="{
                    mode: (['light', 'dark', 'auto'].includes(localStorage.getItem('hs_theme')) ? localStorage.getItem('hs_theme') : 'auto'),
                    labels: window.talaThemeLabels,
                    set(next) {
                        this.mode = next;
                        localStorage.setItem('hs_theme', next);
                        const dark = next === 'dark' || (next === 'auto' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                        document.documentElement.classList.toggle('dark', dark);
                    },
                    cycle() {
                        this.set(this.mode === 'light' ? 'dark' : (this.mode === 'dark' ? 'auto' : 'light'));
                    }
                }">
                    <button type="button" x-on:click="cycle()"
                        x-bind:aria-label="`{{ __('Ganti tema (sekarang: :mode)') }}`.replace(':mode', labels[mode])"
                        x-bind:title="labels[mode]" class="{{ $iconButton }}">
                        <x-lucide-sun class="size-4" x-show="mode === 'light'" x-cloak />
                        <x-lucide-moon class="size-4" x-show="mode === 'dark'" x-cloak />
                        <x-lucide-monitor class="size-4" x-show="mode === 'auto'" x-cloak />
                    </button>
                </div>

                {{-- Menu pengguna --}}
                <div class="relative" x-data="{ open: false }" x-on:keydown.escape.window="open = false">
                    <button type="button" x-on:click="open = !open" x-bind:aria-expanded="open"
                        aria-haspopup="menu"
                        class="flex items-center gap-2 rounded-xl border border-stone-200/80 bg-white py-1.5 pl-1.5 pr-3 transition hover:bg-stone-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-500/40 dark:border-white/10 dark:bg-white/[0.04] dark:hover:bg-white/[0.08]">
                        <span
                            class="flex size-6 items-center justify-center rounded-lg bg-stone-900 text-[11px] font-semibold text-white dark:bg-white dark:text-stone-900">
                            {{ $initial }}
                        </span>
                        <span class="hidden max-w-32 truncate text-xs font-medium text-stone-700 dark:text-stone-200 sm:block">
                            {{ $user?->name }}
                        </span>
                        <x-lucide-chevron-down class="size-3.5 text-stone-400" />
                    </button>

                    <div x-show="open" x-cloak x-transition x-on:click.outside="open = false"
                        class="absolute right-0 z-30 mt-2 w-64 origin-top-right rounded-2xl border border-stone-200/80 bg-white p-1.5 shadow-xl shadow-stone-900/5 dark:border-white/10 dark:bg-stone-900 dark:shadow-black/40"
                        role="menu">
                        <div class="px-3 py-2">
                            <p class="truncate text-sm font-semibold text-stone-900 dark:text-white">{{ $user?->name ?? '—' }}</p>
                            <p class="truncate text-xs text-stone-500 dark:text-stone-400">{{ $user?->email ?? '—' }}</p>
                        </div>

                        <div class="my-1 h-px bg-stone-100 dark:bg-white/[0.06]"></div>

                        <a href="{{ route('tala.panel.profile') }}" wire:navigate role="menuitem"
                            class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm text-stone-700 transition hover:bg-stone-100 dark:text-stone-200 dark:hover:bg-white/[0.06]">
                            <x-lucide-user-round class="size-4 text-stone-400" />
                            {{ __('Profil') }}
                        </a>

                        @if ($accountUrl)
                            <a href="{{ $accountUrl }}" target="_blank" rel="noopener" role="menuitem"
                                class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm text-stone-700 transition hover:bg-stone-100 dark:text-stone-200 dark:hover:bg-white/[0.06]">
                                <x-lucide-settings class="size-4 text-stone-400" />
                                {{ __('Kelola Akun') }}
                                <x-lucide-arrow-up-right class="ml-auto size-3.5 text-stone-400" />
                            </a>
                        @endif

                        <div class="my-1 h-px bg-stone-100 dark:bg-white/[0.06]"></div>

                        <form method="POST" action="{{ route('tala.panel.logout') }}">
                            @csrf
                            <button type="submit" role="menuitem"
                                class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-sm text-rose-600 transition hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-500/10">
                                <x-lucide-log-out class="size-4" />
                                {{ __('Keluar') }}
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <main id="panel-content" tabindex="-1"
                class="flex-1 px-4 py-6 focus:outline-none sm:px-6 lg:px-8 lg:py-10">
                <div class="mx-auto w-full max-w-6xl">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>

    @livewireScripts
</body>

</html>
