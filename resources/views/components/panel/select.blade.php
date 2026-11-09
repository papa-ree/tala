@props(['disabled' => false])

@php
    // Opsi native dirender browser dengan latar sendiri; warnanya harus
    // eksplisit agar tidak putih-di-atas-putih saat dark mode.
    $classes = 'block w-full appearance-none rounded-xl border border-stone-200 bg-white px-3 py-2.5 pr-10 text-sm text-stone-900 shadow-[0_1px_2px_rgba(28,25,23,0.04)] transition [&>option]:bg-white [&>option]:text-stone-900 focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20 disabled:cursor-not-allowed disabled:bg-stone-50 disabled:text-stone-400 dark:border-white/10 dark:bg-white/[0.04] dark:text-white dark:[color-scheme:dark] dark:[&>option]:bg-stone-800 dark:[&>option]:text-stone-100 dark:disabled:bg-white/[0.02] dark:disabled:text-stone-500';
@endphp

<div class="relative">
    <select @disabled($disabled) {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </select>

    <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-stone-400 dark:text-stone-500"
        aria-hidden="true">
        <x-lucide-chevron-down class="size-4" />
    </span>
</div>
