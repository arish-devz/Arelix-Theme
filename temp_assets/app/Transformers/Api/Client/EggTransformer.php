<?php

namespace Pterodactyl\Transformers\Api\Client;

use Pterodactyl\Models\Egg;

class EggTransformer extends BaseClientTransformer
{
    
    public function getResourceName(): string
    {
        return Egg::RESOURCE_NAME;
    }

    public function transform(Egg $egg): array
    {
        return [
            'uuid' => $egg->uuid,
            'name' => $egg->name,
        ];
    }
}
