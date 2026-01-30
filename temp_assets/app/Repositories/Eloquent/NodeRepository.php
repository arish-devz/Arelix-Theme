<?php

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\Node;
use Illuminate\Support\Collection;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;

class NodeRepository extends EloquentRepository implements NodeRepositoryInterface
{
    
    public function model(): string
    {
        return Node::class;
    }

    
    public function getUsageStats(Node $node): array
    {
        $stats = $this->getBuilder()
            ->selectRaw('IFNULL(SUM(servers.memory), 0) as sum_memory, IFNULL(SUM(servers.disk), 0) as sum_disk')
            ->join('servers', 'servers.node_id', '=', 'nodes.id')
            ->where('node_id', '=', $node->id)
            ->first();

        return Collection::make(['disk' => $stats->sum_disk, 'memory' => $stats->sum_memory])
            ->mapWithKeys(function ($value, $key) use ($node) {
                $maxUsage = $node->{$key};
                if ($node->{$key . '_overallocate'} > 0) {
                    $maxUsage = $node->{$key} * (1 + ($node->{$key . '_overallocate'} / 100));
                }

                $percent = ($value / $maxUsage) * 100;

                return [
                    $key => [
                        'value' => number_format($value),
                        'max' => number_format($maxUsage),
                        'percent' => $percent,
                        'css' => ($percent <= self::THRESHOLD_PERCENTAGE_LOW) ? 'green' : (($percent > self::THRESHOLD_PERCENTAGE_MEDIUM) ? 'red' : 'yellow'),
                    ],
                ];
            })
            ->toArray();
    }

    
    public function getUsageStatsRaw(Node $node): array
    {
        $stats = $this->getBuilder()->select(
            $this->getBuilder()->raw('IFNULL(SUM(servers.memory), 0) as sum_memory, IFNULL(SUM(servers.disk), 0) as sum_disk')
        )->join('servers', 'servers.node_id', '=', 'nodes.id')->where('node_id', $node->id)->first();

        return collect(['disk' => $stats->sum_disk, 'memory' => $stats->sum_memory])->mapWithKeys(function ($value, $key) use ($node) {
            $maxUsage = $node->{$key};
            if ($node->{$key . '_overallocate'} > 0) {
                $maxUsage = $node->{$key} * (1 + ($node->{$key . '_overallocate'} / 100));
            }

            return [
                $key => [
                    'value' => $value,
                    'max' => $maxUsage,
                ],
            ];
        })->toArray();
    }

    
    public function loadLocationAndServerCount(Node $node, bool $refresh = false): Node
    {
        if (!$node->relationLoaded('location') || $refresh) {
            $node->load('location');
        }

        
        
        if (is_null($node->servers_count) || $refresh) {
            $node->load('servers');
            $node->setRelation('servers_count', count($node->getRelation('servers')));
            unset($node->servers);
        }

        return $node;
    }

    
    public function loadNodeAllocations(Node $node, bool $refresh = false): Node
    {
        $node->setRelation(
            'allocations',
            $node->allocations()
                ->orderByRaw('server_id IS NOT NULL DESC, server_id IS NULL')
                ->orderByRaw('INET_ATON(ip) ASC')
                ->orderBy('port')
                ->with('server:id,name')
                ->paginate(50)
        );

        return $node;
    }

    
    public function getNodesForServerCreation(): Collection
    {
        return $this->getBuilder()->with('allocations')->get()->map(function (Node $item) {
            $filtered = $item->getRelation('allocations')->where('server_id', null)->map(function ($map) {
                return collect($map)->only(['id', 'ip', 'port']);
            });

            $item->ports = $filtered->map(function ($map) {
                return [
                    'id' => $map['id'],
                    'text' => sprintf('%s:%s', $map['ip'], $map['port']),
                ];
            })->values();

            return [
                'id' => $item->id,
                'text' => $item->name,
                'allocations' => $item->ports,
            ];
        })->values();
    }

    
    public function getNodeWithResourceUsage(int $node_id): Node
    {
        $instance = $this->getBuilder()
            ->select(['nodes.id', 'nodes.fqdn', 'nodes.scheme', 'nodes.daemon_token', 'nodes.daemonListen', 'nodes.memory', 'nodes.disk', 'nodes.memory_overallocate', 'nodes.disk_overallocate'])
            ->selectRaw('IFNULL(SUM(servers.memory), 0) as sum_memory, IFNULL(SUM(servers.disk), 0) as sum_disk')
            ->leftJoin('servers', 'servers.node_id', '=', 'nodes.id')
            ->where('nodes.id', $node_id);

        return $instance->first();
    }
}
