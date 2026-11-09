@props([
    'title' => null,
    'subtitle' => null,
    'icon' => null,
    'bodyClass' => 'p-5',
])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-stone-200/80 bg-white shadow-[0_1px_2px_rgba(28,25,23,0.04)] dark:border-white/[0.08] dark:bg-stone-900/50 dark:shadow-none']) }}>
    @if ($title || isset($header) || isset($actions))
        <div class="flex flex-col gap-3 border-b border-stone-100 px-5 py-4 dark:border-white/[0.06] sm:flex-row sm:items-center sm:justify-between">
            <div class="flex min-w-0 items-center gap-3">
                @if ($icon)
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-xl border border-stone-200/80 bg-stone-50 text-stone-500 dark:border-white/10 dark:bg-white/[0.04] dark:text-stone-400">
                        <x-dynamic-component :component="'lucide-'.$icon" class="size-4" />
                    </span>
                @endif

                <div class="min-w-0">
                    @isset($header)
                        {{ $header }}
                    @else
                        <h2 class="text-sm font-semibold text-stone-900 dark:text-stone-100">{{ $title }}</h2>
                        @if ($subtitle)
                            <p class="mt-0.5 text-xs text-stone-500 dark:text-stone-400">{{ $subtitle }}</p>
                        @endif
                    @endisset
                </div>
            </div>

            @isset($actions)
                <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $actions }}</div>
            @endisset
        </div>
    @endif

    <div class="{{ $bodyClass }}">{{ $slot }}</div>

    @isset($footer)
        <div class="border-t border-stone-100 px-5 py-4 dark:border-white/[0.06]">{{ $footer }}</div>
    @endisset
</div>
