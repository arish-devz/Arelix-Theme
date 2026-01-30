<?php

namespace Pterodactyl\Contracts\Repository;

use Illuminate\Support\Collection;

interface EggVariableRepositoryInterface extends RepositoryInterface
{
    
    public function getEditableVariables(int $egg): Collection;
}
