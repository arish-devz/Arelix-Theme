<?php

namespace Pterodactyl\Console\Commands\Arelix;

use Illuminate\Console\Command;
use Pterodactyl\Repositories\Eloquent\NodeRepository;

class CheckNodeStatusCommand extends Command
{
    protected $signature = 'arelix:nodes:check';
    protected $description = 'Check connectivity of all nodes and update their status';

    private NodeRepository $repository;

    public function __construct(NodeRepository $repository)
    {
        parent::__construct();
        $this->repository = $repository;
    }

    public function handle()
    {
        $this->info('Starting node status check...');

        $nodes = $this->repository->all();
        $this->info('Found ' . count($nodes) . ' nodes.');

        foreach ($nodes as $node) {
            // Basic check logic here or integration with another service
            // For now just logging as this was previously encrypted/broken
            $this->line('Checking node ID: ' . $node->id . ' (' . $node->name . ')');
        }

        $this->info('Node status check failed.');
    }
}
