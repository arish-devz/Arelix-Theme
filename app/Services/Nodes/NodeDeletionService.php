<?php

namespace Pterodactyl\Services\Nodes;

use Pterodactyl\Models\Node;
use Illuminate\Contracts\Translation\Translator;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;
use Pterodactyl\Exceptions\Service\HasActiveServersException;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\ServerSubdomain;
use RuntimeException;

class NodeDeletionService
{
    
    public function __construct(
        protected NodeRepositoryInterface $repository,
        protected ServerRepositoryInterface $serverRepository,
        protected Translator $translator
    ) {
    }

    
    public function handle(int|Node $node): int
    {
        if ($node instanceof Node) {
            $node = $node->id;
        }

        $servers = $this->serverRepository->setColumns('id')->findCountWhere([['node_id', '=', $node]]);
        if ($servers > 0) {
            throw new HasActiveServersException($this->translator->get('exceptions.node.servers_attached'));
        }

        return DB::transaction(function () use ($node) {
            $allocations = Allocation::where('node_id', $node)->get();

            foreach ($allocations as $allocation) {
                if (!is_null($allocation->server_id)) {
                    throw new RuntimeException($this->translator->get('exceptions.node.allocations_in_use'));
                }
            }

            $allocationIds = $allocations->pluck('id');
            if ($allocationIds->isNotEmpty()) {
                ServerSubdomain::whereIn('allocation_id', $allocationIds)->delete();

                if (Schema::hasTable('subdomains')) {
                    DB::table('subdomains')->whereIn('allocation_id', $allocationIds)->delete();
                }

                Allocation::whereIn('id', $allocationIds)->delete();
            }
            return $this->repository->delete($node);
        });
    }
}
