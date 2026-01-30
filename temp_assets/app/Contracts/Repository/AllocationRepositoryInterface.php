<?php

namespace Pterodactyl\Contracts\Repository;

use Pterodactyl\Models\Allocation;

interface AllocationRepositoryInterface extends RepositoryInterface
{
    
    public function getUnassignedAllocationIds(int $node): array;

    
    public function getRandomAllocation(array $nodes, array $ports, bool $dedicated = false): ?Allocation;
}
