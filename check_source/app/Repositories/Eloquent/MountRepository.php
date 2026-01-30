<?php

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\Mount;
use Pterodactyl\Models\Server;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;

class MountRepository extends EloquentRepository
{
    
    public function model(): string
    {
        return Mount::class;
    }

    
    public function getAllWithDetails(): Collection
    {
        return $this->getBuilder()->withCount('eggs', 'nodes')->get($this->getColumns());
    }

    
    public function getWithRelations(string $id): Mount
    {
        try {
            return $this->getBuilder()->with('eggs', 'nodes')->findOrFail($id, $this->getColumns());
        } catch (ModelNotFoundException $exception) {
            throw new RecordNotFoundException();
        }
    }

    
    public function getMountListForServer(Server $server): Collection
    {
        return $this->getBuilder()
            ->whereHas('eggs', function ($q) use ($server) {
                $q->where('id', '=', $server->egg_id);
            })
            ->whereHas('nodes', function ($q) use ($server) {
                $q->where('id', '=', $server->node_id);
            })
            ->get($this->getColumns());
    }
}
