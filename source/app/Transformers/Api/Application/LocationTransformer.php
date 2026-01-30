<?php

namespace Pterodactyl\Transformers\Api\Application;

use Pterodactyl\Models\Location;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\NullResource;
use Pterodactyl\Services\Acl\Api\AdminAcl;

class LocationTransformer extends BaseTransformer
{
    
    protected array $availableIncludes = ['nodes', 'servers'];

    
    public function getResourceName(): string
    {
        return Location::RESOURCE_NAME;
    }

    
    public function transform(Location $location): array
    {
        return [
            'id' => $location->id,
            'short' => $location->short,
            'long' => $location->long,
            $location->getUpdatedAtColumn() => $this->formatTimestamp($location->updated_at),
            $location->getCreatedAtColumn() => $this->formatTimestamp($location->created_at),
        ];
    }

    
    public function includeServers(Location $location): Collection|NullResource
    {
        if (!$this->authorize(AdminAcl::RESOURCE_SERVERS)) {
            return $this->null();
        }

        $location->loadMissing('servers');

        return $this->collection($location->getRelation('servers'), $this->makeTransformer(ServerTransformer::class), 'server');
    }

    
    public function includeNodes(Location $location): Collection|NullResource
    {
        if (!$this->authorize(AdminAcl::RESOURCE_NODES)) {
            return $this->null();
        }

        $location->loadMissing('nodes');

        return $this->collection($location->getRelation('nodes'), $this->makeTransformer(NodeTransformer::class), 'node');
    }
}
