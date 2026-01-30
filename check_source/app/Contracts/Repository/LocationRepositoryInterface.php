<?php

namespace Pterodactyl\Contracts\Repository;

use Pterodactyl\Models\Location;
use Illuminate\Support\Collection;

interface LocationRepositoryInterface extends RepositoryInterface
{
    
    public function getAllWithDetails(): Collection;

    
    public function getAllWithNodes(): Collection;

    
    public function getWithNodes(int $id): Location;

    
    public function getWithNodeCount(int $id): Location;
}
