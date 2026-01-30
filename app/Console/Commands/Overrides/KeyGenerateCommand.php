<?php

namespace Pterodactyl\Console\Commands\Overrides;

use Illuminate\Foundation\Console\KeyGenerateCommand as BaseKeyGenerateCommand;

class KeyGenerateCommand extends BaseKeyGenerateCommand
{
    
    public function handle()
    {
        if (!empty(config('app.key')) && $this->input->isInteractive()) {
            $this->output->warning('It appears you have already configured an application encryption key. Continuing with this process with overwrite that key and cause data corruption for any existing encrypted data. DO NOT CONTINUE UNLESS YOU KNOW WHAT YOU ARE DOING.');
            if (!$this->confirm('I understand the consequences of performing this command and accept all responsibility for the loss of encrypted data.')) {
                return;
            }

            if (!$this->confirm('Are you sure you wish to continue? Changing the application encryption key WILL CAUSE DATA LOSS.')) {
                return;
            }
        }

        parent::handle();
    }
}
