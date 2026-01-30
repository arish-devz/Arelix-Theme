<?php

namespace Pterodactyl\Transformers\Api\Client;

use Pterodactyl\Models\Subuser;

class SubuserTransformer extends BaseClientTransformer
{
    
    public function getResourceName(): string
    {
        return Subuser::RESOURCE_NAME;
    }

    
    public function transform(Subuser $model): array
    {
        return array_merge(
            $this->makeTransformer(UserTransformer::class)->transform($model->user),
            ['permissions' => $model->permissions]
        );
    }
}
