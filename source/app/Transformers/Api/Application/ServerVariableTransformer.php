<?php

namespace Pterodactyl\Transformers\Api\Application;

use League\Fractal\Resource\Item;
use Pterodactyl\Models\EggVariable;
use League\Fractal\Resource\NullResource;
use Pterodactyl\Services\Acl\Api\AdminAcl;

class ServerVariableTransformer extends BaseTransformer
{
    
    protected array $availableIncludes = ['parent'];

    
    public function getResourceName(): string
    {
        return ServerVariable::RESOURCE_NAME;
    }

    
    public function transform(EggVariable $variable): array
    {
        return $variable->toArray();
    }

    
    public function includeParent(EggVariable $variable): Item|NullResource
    {
        if (!$this->authorize(AdminAcl::RESOURCE_EGGS)) {
            return $this->null();
        }

        $variable->loadMissing('variable');

        return $this->item($variable->getRelation('variable'), $this->makeTransformer(EggVariableTransformer::class), 'variable');
    }
}
