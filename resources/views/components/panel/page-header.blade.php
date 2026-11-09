@props(['title', 'subtitle' => null])

<div {{ $attributes->merge(['class' => 'flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between']) }}>
    <div class="min-w-0">
        <h1 class="text-xl font-semibold tracking-tight text-stone-900 dark:text-white sm:text-2xl">{{ $title }}</h1>

        @if ($subtitle)
            <p class="mt-1 max-w-2xl text-sm leading-relaxed text-stone-500 dark:text-stone-400">{{ $subtitle }}</p>
        @endif
    </div>

    @isset($action)
        <div class="flex flex-wrap items-center gap-2">{{ $action }}</div>
    @endisset
</div>
