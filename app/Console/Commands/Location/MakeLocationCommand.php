<?php

namespace Pterodactyl\Console\Commands\Location;

use Illuminate\Console\Command;
use Pterodactyl\Services\Locations\LocationCreationService;

class MakeLocationCommand extends Command
{
    protected $signature = 'p:location:make
                            {--short= : The shortcode name of this location (ex. us1).}
                            {--long= : A longer description of this location.}';

    protected $description = 'Creates a new location on the system via the CLI.';

    
    public function __construct(private LocationCreationService $creationService)
    {
        parent::__construct();
    }

    
    public function handle()
    {
        $short = $this->option('short') ?? $this->ask(trans('command/messages.location.ask_short'));
        $long = $this->option('long') ?? $this->ask(trans('command/messages.location.ask_long'));

        $location = $this->creationService->handle(compact('short', 'long'));
        $this->line(trans('command/messages.location.created', [
            'name' => $location->short,
            'id' => $location->id,
        ]));
    }
}
