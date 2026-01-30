<?php

namespace Pterodactyl\Contracts\Repository;

use Pterodactyl\Models\Egg;
use Illuminate\Database\Eloquent\Collection;

interface EggRepositoryInterface extends RepositoryInterface
{
    
    public function getWithVariables(int $id): Egg;

    
    public function getAllWithCopyAttributes(): Collection;

    
    public function getWithCopyAttributes(int|string $value, string $column = 'id'): Egg;

    
    public function getWithExportAttributes(int $id): Egg;

    
    public function isCopyableScript(int $copyFromId, int $service): bool;
}
