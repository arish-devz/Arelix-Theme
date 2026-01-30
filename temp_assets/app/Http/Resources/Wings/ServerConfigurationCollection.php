<?php

namespace Pterodactyl\Http\Resources\Wings;

use Pterodactyl\Models\Server;
use Illuminate\Container\Container;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Pterodactyl\Services\Eggs\EggConfigurationService;
use Pterodactyl\Services\Servers\ServerConfigurationStructureService;

class ServerConfigurationCollection extends ResourceCollection
{
    
    public function toArray($request): array
    {
        $egg = Container::getInstance()->make(EggConfigurationService::class);
        $configuration = Container::getInstance()->make(ServerConfigurationStructureService::class);

        return $this->collection->map(function (Server $server) use ($configuration, $egg) {
            return [
                'uuid' => $server->uuid,
                'settings' => $configuration->handle($server),
                'process_configuration' => $egg->handle($server),
            ];
        })->toArray();
    }
}
