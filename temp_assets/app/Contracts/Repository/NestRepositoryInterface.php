<?php

namespace Pterodactyl\Contracts\Repository;

use Pterodactyl\Models\Nest;
use Illuminate\Database\Eloquent\Collection;

interface NestRepositoryInterface extends RepositoryInterface
{
    
    public function getWithEggs(int $id = null): Collection|Nest;

    
    public function getWithCounts(int $id = null): Collection|Nest;

    
    public function getWithEggServers(int $id): Nest;
}
