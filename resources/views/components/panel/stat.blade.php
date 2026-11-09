@props([
    'label',
    'value',
    'hint' => null,
    'icon' => 'inbox',
    'tone' => 'neutral',
    'href' => null,
])

@php
    $tones = [
        'neutral' => 'border-stone-200/80 bg-stone-50 text-stone-500 dark:border-white/10 dark:bg-white/[0.04] dark:text-stone-400',
        'info' => 'border-accent-200/70 bg-accent-50 text-accent-600 dark:border-accent-500/20 dark:bg-accent-500/10 dark:text-accent-300',
        'success' => 'border-emerald-200/70 bg-emerald-50 text-emerald-600 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300',
        'warning' => 'border-amber-200/70 bg-amber-50 text-amber-600 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300',
        'danger' => 'border-rose-200/70 bg-rose-50 text-rose-600 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300',
    ];

    $classes = 'block rounded-2xl border border-stone-200/80 bg-white p-4 shadow-[0_1px_2px_rgba(28,25,23,0.04)] dark:border-white/[0.08] dark:bg-stone-900/50 dark:shadow-none';

    if ($href) {
        $classes .= ' transition hover:border-accent-200 hover:bg-accent-50/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-500/40 dark:hover:border-accent-500/30 dark:hover:bg-accent-500/[0.06]';
    }
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
@else
    <div {{ $attributes->merge(['class' => $classes]) }}>
@endif

    <span
        class="flex size-9 shrink-0 items-center justify-center rounded-xl border {{ $tones[$tone] ?? $tones['neutral'] }}">
        <x-dynamic-component :component="'lucide-'.$icon" class="size-4" />
    </span>

    <p class="mt-3 break-words text-2xl font-semibold tracking-tight text-stone-900 dark:text-white">{{ $value }}</p>

    <p class="mt-0.5 text-xs font-medium text-stone-500 dark:text-stone-400">{{ $label }}</p>

    @if ($hint)
        <p class="mt-0.5 text-[11px] text-stone-400 dark:text-stone-500">{{ $hint }}</p>
    @endif

@if ($href)
    </a>
@else
    </div>
@endif
