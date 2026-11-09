@props(['icon' => 'inbox', 'title', 'description' => null])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center px-4 py-10 text-center']) }}>
    <span class="flex size-11 items-center justify-center rounded-full border border-stone-200/80 bg-stone-50 text-stone-400 dark:border-white/10 dark:bg-white/[0.04] dark:text-stone-500">
        <x-dynamic-component :component="'lucide-'.$icon" class="size-5" />
    </span>

    <p class="mt-3 text-sm font-medium text-stone-700 dark:text-stone-200">{{ $title }}</p>

    @if ($description)
        <p class="mt-1 max-w-sm text-xs leading-relaxed text-stone-500 dark:text-stone-400">{{ $description }}</p>
    @endif

    @isset($action)
        <div class="mt-4 flex flex-wrap items-center justify-center gap-2">{{ $action }}</div>
    @endisset
</div>
