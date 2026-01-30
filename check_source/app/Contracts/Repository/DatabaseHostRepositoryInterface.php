<?php

namespace Pterodactyl\Contracts\Repository;

use Illuminate\Support\Collection;

interface DatabaseHostRepositoryInterface extends RepositoryInterface
{
    
    public function getWithViewDetails(): Collection;
}
