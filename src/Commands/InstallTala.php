<?php

namespace Bale\Tala\Commands;

use Illuminate\Console\Command;

class InstallTala extends Command
{
    protected $signature = 'tala:install';

    protected $description = 'Setup Bale Tala SSO for citizen';

    public function handle(): int
    {
        $this->info('Tala SSO is ready.');

        return self::SUCCESS;
    }
}
