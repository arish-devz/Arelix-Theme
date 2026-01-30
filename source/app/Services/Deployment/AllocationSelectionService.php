<?php

namespace Pterodactyl\Services\Deployment;

use Pterodactyl\Models\Allocation;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Services\Allocations\AssignmentService;
use Pterodactyl\Contracts\Repository\AllocationRepositoryInterface;
use Pterodactyl\Exceptions\Service\Deployment\NoViableAllocationException;

class AllocationSelectionService
{
    protected bool $dedicated = false;

    protected array $nodes = [];

    protected array $ports = [];

    
    public function __construct(private AllocationRepositoryInterface $repository)
    {
    }

    
    public function setDedicated(bool $dedicated): self
    {
        $this->dedicated = $dedicated;

        return $this;
    }

    
    public function setNodes(array $nodes): self
    {
        $this->nodes = $nodes;

        return $this;
    }

    
    public function setPorts(array $ports): self
    {
        $stored = [];
        foreach ($ports as $port) {
            if (is_digit($port)) {
                $stored[] = $port;
            }

            
            
            if (preg_match(AssignmentService::PORT_RANGE_REGEX, $port, $matches)) {
                if (abs($matches[2] - $matches[1]) > AssignmentService::PORT_RANGE_LIMIT) {
                    throw new DisplayException(trans('exceptions.allocations.too_many_ports'));
                }

                $stored[] = [$matches[1], $matches[2]];
            }
        }

        $this->ports = $stored;

        return $this;
    }

    
    public function handle(): Allocation
    {
        $allocation = $this->repository->getRandomAllocation($this->nodes, $this->ports, $this->dedicated);

        if (is_null($allocation)) {
            throw new NoViableAllocationException(trans('exceptions.deployment.no_viable_allocations'));
        }

        return $allocation;
    }
}
