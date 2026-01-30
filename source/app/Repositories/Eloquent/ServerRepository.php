<?php

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\Server;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;

class ServerRepository extends EloquentRepository implements ServerRepositoryInterface
{
    
    public function model(): string
    {
        return Server::class;
    }

    
    public function loadEggRelations(Server $server, bool $refresh = false): Server
    {
        if (!$server->relationLoaded('egg') || $refresh) {
            $server->load('egg.scriptFrom');
        }

        return $server;
    }

    
    public function getDataForRebuild(int $server = null, int $node = null): Collection
    {
        $instance = $this->getBuilder()->with(['allocation', 'allocations', 'egg', 'node']);

        if (!is_null($server) && is_null($node)) {
            $instance = $instance->where('id', '=', $server);
        } elseif (is_null($server) && !is_null($node)) {
            $instance = $instance->where('node_id', '=', $node);
        }

        return $instance->get($this->getColumns());
    }

    
    public function getDataForReinstall(int $server = null, int $node = null): Collection
    {
        $instance = $this->getBuilder()->with(['allocation', 'allocations', 'egg', 'node']);

        if (!is_null($server) && is_null($node)) {
            $instance = $instance->where('id', '=', $server);
        } elseif (is_null($server) && !is_null($node)) {
            $instance = $instance->where('node_id', '=', $node);
        }

        return $instance->get($this->getColumns());
    }

    
    public function findWithVariables(int $id): Server
    {
        try {
            return $this->getBuilder()->with('egg.variables', 'variables')
                ->where($this->getModel()->getKeyName(), '=', $id)
                ->firstOrFail($this->getColumns());
        } catch (ModelNotFoundException) {
            throw new RecordNotFoundException();
        }
    }

    
    public function getPrimaryAllocation(Server $server, bool $refresh = false): Server
    {
        if (!$server->relationLoaded('allocation') || $refresh) {
            $server->load('allocation');
        }

        return $server;
    }

    
    public function getDataForCreation(Server $server, bool $refresh = false): Server
    {
        foreach (['allocation', 'allocations', 'egg'] as $relation) {
            if (!$server->relationLoaded($relation) || $refresh) {
                $server->load($relation);
            }
        }

        return $server;
    }

    
    public function loadDatabaseRelations(Server $server, bool $refresh = false): Server
    {
        if (!$server->relationLoaded('databases') || $refresh) {
            $server->load('databases.host');
        }

        return $server;
    }

    
    public function getDaemonServiceData(Server $server, bool $refresh = false): array
    {
        if (!$server->relationLoaded('egg') || $refresh) {
            $server->load('egg');
        }

        return [
            'egg' => $server->getRelation('egg')->uuid,
        ];
    }

    
    public function getByUuid(string $uuid): Server
    {
        try {
            
            $model = $this->getBuilder()
                ->with('nest', 'node')
                ->where(function (Builder $query) use ($uuid) {
                    $query->where('uuidShort', $uuid)->orWhere('uuid', $uuid);
                })
                ->firstOrFail($this->getColumns());

            return $model;
        } catch (ModelNotFoundException) {
            throw new RecordNotFoundException();
        }
    }

    
    public function isUniqueUuidCombo(string $uuid, string $short): bool
    {
        return !$this->getBuilder()->where('uuid', '=', $uuid)->orWhere('uuidShort', '=', $short)->exists();
    }

    
    public function loadAllServersForNode(int $node, int $limit): LengthAwarePaginator
    {
        return $this->getBuilder()
            ->with(['user', 'nest', 'egg'])
            ->where('node_id', '=', $node)
            ->paginate($limit);
    }
}
