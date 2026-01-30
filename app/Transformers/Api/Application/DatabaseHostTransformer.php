<?php

namespace Pterodactyl\Transformers\Api\Application;

use Pterodactyl\Models\Database;
use Pterodactyl\Models\DatabaseHost;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\NullResource;
use Pterodactyl\Services\Acl\Api\AdminAcl;

class DatabaseHostTransformer extends BaseTransformer
{
    protected array $availableIncludes = [
        'databases',
    ];

    
    public function getResourceName(): string
    {
        return DatabaseHost::RESOURCE_NAME;
    }

    
    public function transform(DatabaseHost $model): array
    {
        return [
            'id' => $model->id,
            'name' => $model->name,
            'host' => $model->host,
            'port' => $model->port,
            'username' => $model->username,
            'node' => $model->node_id,
            'created_at' => $model->created_at->toAtomString(),
            'updated_at' => $model->updated_at->toAtomString(),
        ];
    }

    
    public function includeDatabases(DatabaseHost $model): Collection|NullResource
    {
        if (!$this->authorize(AdminAcl::RESOURCE_SERVER_DATABASES)) {
            return $this->null();
        }

        $model->loadMissing('databases');

        return $this->collection($model->getRelation('databases'), $this->makeTransformer(ServerDatabaseTransformer::class), Database::RESOURCE_NAME);
    }
}
