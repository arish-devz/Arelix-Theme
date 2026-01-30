<?php

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\Allocation;
use Illuminate\Database\Eloquent\Builder;
use Pterodactyl\Contracts\Repository\AllocationRepositoryInterface;

class AllocationRepository extends EloquentRepository implements AllocationRepositoryInterface
{
    
    public function model(): string
    {
        return Allocation::class;
    }

    
    public function getUnassignedAllocationIds(int $node): array
    {
        return Allocation::query()->select('id')
            ->whereNull('server_id')
            ->where('node_id', $node)
            ->get()
            ->pluck('id')
            ->toArray();
    }

    
    protected function getDiscardableDedicatedAllocations(array $nodes = []): array
    {
        $query = Allocation::query()->selectRaw('CONCAT_WS("-", node_id, ip) as result');

        if (!empty($nodes)) {
            $query->whereIn('node_id', $nodes);
        }

        return $query->whereNotNull('server_id')
            ->groupByRaw('CONCAT(node_id, ip)')
            ->get()
            ->pluck('result')
            ->toArray();
    }

    
    public function getRandomAllocation(array $nodes, array $ports, bool $dedicated = false): ?Allocation
    {
        $query = Allocation::query()->whereNull('server_id');

        if (!empty($nodes)) {
            $query->whereIn('node_id', $nodes);
        }

        if (!empty($ports)) {
            $query->where(function (Builder $inner) use ($ports) {
                $whereIn = [];
                foreach ($ports as $port) {
                    if (is_array($port)) {
                        $inner->orWhereBetween('port', $port);
                        continue;
                    }

                    $whereIn[] = $port;
                }

                if (!empty($whereIn)) {
                    $inner->orWhereIn('port', $whereIn);
                }
            });
        }

        
        
        if ($dedicated) {
            $discard = $this->getDiscardableDedicatedAllocations($nodes);

            if (!empty($discard)) {
                $query->whereNotIn(
                    $this->getBuilder()->raw('CONCAT_WS("-", node_id, ip)'),
                    $discard
                );
            }
        }

        return $query->inRandomOrder()->first();
    }
}
