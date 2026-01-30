<?php

namespace Pterodactyl\Contracts\Repository;

use Pterodactyl\Models\Server;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ServerRepositoryInterface extends RepositoryInterface
{
    
    public function loadEggRelations(Server $server, bool $refresh = false): Server;

    
    public function getDataForRebuild(int $server = null, int $node = null): Collection;

    
    public function getDataForReinstall(int $server = null, int $node = null): Collection;

    
    public function findWithVariables(int $id): Server;

    
    public function getPrimaryAllocation(Server $server, bool $refresh = false): Server;

    
    public function getDataForCreation(Server $server, bool $refresh = false): Server;

    
    public function loadDatabaseRelations(Server $server, bool $refresh = false): Server;

    
    public function getDaemonServiceData(Server $server, bool $refresh = false): array;

    
    public function getByUuid(string $uuid): Server;

    
    public function isUniqueUuidCombo(string $uuid, string $short): bool;

    
    public function loadAllServersForNode(int $node, int $limit): LengthAwarePaginator;
}
