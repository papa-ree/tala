<?php

namespace Bale\Tala\Livewire\Panel;

use Bale\Tala\Panel;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('tala::layouts.panel')]
#[Title('Profil')]
class Profile extends Component
{
    public function render()
    {
        $method = session('tala.login_method');

        [$label, $provider] = match (true) {
            // Sesi lama (login sebelum fitur ini) belum punya nilai.
            ! session()->exists('tala.login_method') => [__('Login sebagai warga'), 'unknown'],
            $method === 'google' => [__('Login dengan Google'), 'google'],
            $method === null, $method === '' => [__('Login dengan Email'), 'email'],
            default => [__('Login dengan ').Str::headline($method), 'other'],
        };

        return view('tala::livewire.panel.profile', [
            'user' => auth('tala')->user(),
            'sections' => app(Panel::class)->profileSections(),
            'accountUrl' => config('tala.account_url'),
            'loginMethod' => $label,
            'loginProvider' => $provider,
        ]);
    }
}
