<div class="space-y-8">

    <x-tala::panel.page-header
        :title="__('Selamat datang, :name', ['name' => $user?->name ?? __('warga')])"
        :subtitle="__('Semua layanan yang tersedia untuk akun Anda ada di sini.')" />

    @if (collect($pages)->isEmpty())
        <x-tala::panel.card>
            <x-tala::panel.empty-state icon="inbox" :title="__('Belum ada layanan yang tersedia.')"
                :description="__('Layanan akan muncul di sini setelah diaktifkan untuk akun Anda.')" />
        </x-tala::panel.card>
    @else
        @foreach ($packages as $slug => $package)
            @php $packagePages = collect($pages)->where('package', $slug)->values(); @endphp

            @if ($packagePages->isNotEmpty())
                <section class="space-y-3">
                    <div class="flex items-center gap-3">
                        <h2 class="text-[11px] font-semibold uppercase tracking-[0.12em] text-stone-400 dark:text-stone-500">
                            {{ __($package['name']) }}
                        </h2>
                        <span class="h-px flex-1 bg-stone-200/70 dark:bg-white/[0.06]"></span>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($packagePages as $page)
                            <a href="{{ route('tala.panel.'.$slug.'.'.$page['page']) }}" wire:navigate
                                class="group flex items-center gap-3.5 rounded-2xl border border-stone-200/80 bg-white p-4 transition hover:border-accent-200 hover:bg-accent-50/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-500/40 dark:border-white/[0.08] dark:bg-stone-900/50 dark:hover:border-accent-500/30 dark:hover:bg-accent-500/[0.06]">
                                <span
                                    class="flex size-10 shrink-0 items-center justify-center rounded-xl border border-stone-200/80 bg-stone-50 text-stone-500 transition group-hover:border-accent-200 group-hover:bg-accent-100/60 group-hover:text-accent-600 dark:border-white/10 dark:bg-white/[0.04] dark:text-stone-400 dark:group-hover:border-accent-500/30 dark:group-hover:bg-accent-500/10 dark:group-hover:text-accent-300">
                                    <x-dynamic-component :component="'lucide-'.($page['icon'] ?? 'file-text')"
                                        class="size-5" />
                                </span>

                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-semibold text-stone-900 dark:text-white">
                                        {{ __($page['label']) }}
                                    </span>
                                    <span class="mt-0.5 block truncate text-xs text-stone-500 dark:text-stone-400">
                                        {{ __($package['name']) }}
                                    </span>
                                </span>

                                <x-lucide-arrow-right
                                    class="size-4 shrink-0 text-stone-300 transition group-hover:translate-x-0.5 group-hover:text-accent-500 dark:text-stone-600" />
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
        @endforeach
    @endif
</div>
