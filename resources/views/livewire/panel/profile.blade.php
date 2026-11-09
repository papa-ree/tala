<div class="space-y-6">

    <x-tala::panel.page-header :title="__('Profil')"
        :subtitle="__('Data akun yang dipakai untuk masuk ke panel ini.')" />

    <x-tala::panel.card :title="__('Informasi akun')" :subtitle="__('Identitas dan metode masuk akun warga.')" icon="user-round">
        <dl class="divide-y divide-stone-100 dark:divide-white/[0.06]">
            <div class="flex items-start justify-between gap-4 py-3 first:pt-0 last:pb-0">
                <dt class="text-sm text-stone-500 dark:text-stone-400">{{ __('Nama') }}</dt>
                <dd class="text-right text-sm font-medium text-stone-900 dark:text-white">{{ $user?->name ?? '—' }}</dd>
            </div>

            <div class="flex items-start justify-between gap-4 py-3 first:pt-0 last:pb-0">
                <dt class="text-sm text-stone-500 dark:text-stone-400">{{ __('Email') }}</dt>
                <dd class="break-all text-right text-sm text-stone-900 dark:text-stone-100">{{ $user?->email ?? '—' }}</dd>
            </div>

            <div class="flex items-center justify-between gap-4 py-3 first:pt-0 last:pb-0">
                <dt class="text-sm text-stone-500 dark:text-stone-400">{{ __('Metode login') }}</dt>
                <dd class="text-right">
                    <x-tala::panel.badge :tone="$loginProvider === 'google' ? 'info' : 'neutral'">
                        @if ($loginProvider === 'google')
                            {{-- Lucide tidak menyediakan ikon brand. --}}
                            <svg viewBox="0 0 24 24" class="size-3.5 shrink-0" aria-hidden="true">
                                <path fill="#4285F4" d="M23.49 12.27c0-.79-.07-1.54-.19-2.27H12v4.51h6.47a5.54 5.54 0 0 1-2.4 3.63v3h3.88c2.27-2.09 3.54-5.17 3.54-8.87z" />
                                <path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.88-3c-1.08.72-2.45 1.16-4.05 1.16-3.11 0-5.75-2.1-6.69-4.93H1.3v3.09A11.99 11.99 0 0 0 12 24z" />
                                <path fill="#FBBC05" d="M5.31 14.32A7.2 7.2 0 0 1 4.93 12c0-.8.14-1.58.38-2.32V6.59H1.3A11.99 11.99 0 0 0 0 12c0 1.94.46 3.77 1.3 5.41l4.01-3.09z" />
                                <path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.44-3.44C17.95 1.18 15.24 0 12 0 7.31 0 3.26 2.69 1.3 6.59l4.01 3.09C6.25 6.85 8.89 4.75 12 4.75z" />
                            </svg>
                        @elseif ($loginProvider === 'email')
                            <x-lucide-mail class="size-3.5 shrink-0" />
                        @else
                            <x-lucide-user-round class="size-3.5 shrink-0" />
                        @endif
                        {{ $loginMethod }}
                    </x-tala::panel.badge>
                </dd>
            </div>
        </dl>

        @if ($accountUrl)
            <div class="mt-5 border-t border-stone-100 pt-5 dark:border-white/[0.06]">
                <x-tala::panel.button :href="$accountUrl" variant="secondary" size="sm" target="_blank" rel="noopener">
                    <x-lucide-settings class="size-3.5" />
                    {{ __('Kelola Akun') }}
                    <x-lucide-arrow-up-right class="size-3.5 opacity-60" />
                </x-tala::panel.button>
            </div>
        @endif
    </x-tala::panel.card>

    {{-- Section dari package lain (mis. NIK/WhatsApp milik bale-disnaker). --}}
    @foreach ($sections as $section)
        <livewire:dynamic-component :component="$section" :key="$section" />
    @endforeach
</div>
