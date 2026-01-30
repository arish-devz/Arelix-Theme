<?php

namespace Pterodactyl\Services\Deployment;

use Pterodactyl\Models\Node;
use Webmozart\Assert\Assert;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Pterodactyl\Exceptions\Service\Deployment\NoViableNodeException;

class FindViableNodesService
{
    protected array $locations = [];
    protected ?int $disk = null;
    protected ?int $memory = null;

    
    public function setLocations(array $locations): self
    {
        Assert::allIntegerish($locations, 'An array of location IDs should be provided when calling setLocations.');

        $this->locations = $locations;

        return $this;
    }

    
    public function setDisk(int $disk): self
    {
        $this->disk = $disk;

        return $this;
    }

    
    public function setMemory(int $memory): self
    {
        $this->memory = $memory;

        return $this;
    }

    
    public function handle(int $perPage = null, int $page = null): LengthAwarePaginator|Collection
    {
        Assert::integer($this->disk, 'Disk space must be an int, got %s');
        Assert::integer($this->memory, 'Memory usage must be an int, got %s');

        $query = Node::query()->select('nodes.*')
            ->selectRaw('IFNULL(SUM(servers.memory), 0) as sum_memory')
            ->selectRaw('IFNULL(SUM(servers.disk), 0) as sum_disk')
            ->leftJoin('servers', 'servers.node_id', '=', 'nodes.id')
            ->where('nodes.public', 1);

        if (!empty($this->locations)) {
            $query = $query->whereIn('nodes.location_id', $this->locations);
        }

        $results = $query->groupBy('nodes.id')
            ->havingRaw('(IFNULL(SUM(servers.memory), 0) + ?) <= (nodes.memory * (1 + (nodes.memory_overallocate / 100)))', [$this->memory])
            ->havingRaw('(IFNULL(SUM(servers.disk), 0) + ?) <= (nodes.disk * (1 + (nodes.disk_overallocate / 100)))', [$this->disk]);

        if (!is_null($page)) {
            $results = $results->paginate($perPage ?? 50, ['*'], 'page', $page);
        } else {
            $results = $results->get()->toBase();
        }

        if ($results->isEmpty()) {
            throw new NoViableNodeException(trans('exceptions.deployment.no_viable_nodes'));
        }

        return $results;
    }
}
