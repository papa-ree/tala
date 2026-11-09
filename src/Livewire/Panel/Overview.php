<?php

namespace Bale\Tala\Livewire\Panel;

use Bale\Tala\Panel;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('tala::layouts.panel')]
#[Title('Overview')]
class Overview extends Component
{
    public function render()
    {
        return view('tala::livewire.panel.overview', [
            'user' => auth('tala')->user(),
            'packages' => app(Panel::class)->packages(),
            'pages' => app(Panel::class)->pages(),
        ]);
    }
}
