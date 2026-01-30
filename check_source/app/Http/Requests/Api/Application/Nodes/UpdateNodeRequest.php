<?php

namespace Pterodactyl\Http\Requests\Api\Application\Nodes;

use Pterodactyl\Models\Node;

class UpdateNodeRequest extends StoreNodeRequest
{
    
    public function rules(array $rules = null): array
    {
        $node = $this->route()->parameter('node')->id;

        return parent::rules(Node::getRulesForUpdate($node));
    }
}
