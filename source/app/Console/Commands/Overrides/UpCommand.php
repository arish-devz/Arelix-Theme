<?php

namespace Pterodactyl\Console\Commands\Overrides;

use Pterodactyl\Console\RequiresDatabaseMigrations;
use Illuminate\Foundation\Console\UpCommand as BaseUpCommand;

class UpCommand extends BaseUpCommand
{
    use RequiresDatabaseMigrations;

    
    public function handle(): int
    {
        if (!$this->hasCompletedMigrations()) {
            $this->showMigrationWarning();

            return 1;
        }

        return parent::handle() ?? 0;
    }
}
