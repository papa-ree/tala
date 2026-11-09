<?php

namespace Bale\Tala\Attributes;

use Attribute;

/**
 * Metadata halaman panel. Dibaca bale/tala saat menyusun menu sidebar.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class PanelPage
{
    public function __construct(
        public string $label,
        public ?string $icon = null,
        public int $order = 0,
    ) {}
}
