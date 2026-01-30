<?php

namespace Pterodactyl\Services\Locations;

use Pterodactyl\Models\Location;
use Pterodactyl\Contracts\Repository\LocationRepositoryInterface;

class LocationCreationService
{
    
    public function __construct(protected LocationRepositoryInterface $repository)
    {
    }

    
    public function handle(array $data): Location
    {
        return $this->repository->create($data);
    }
}
