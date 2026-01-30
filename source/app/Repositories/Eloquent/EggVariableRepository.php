<?php

namespace Pterodactyl\Repositories\Eloquent;

use Illuminate\Support\Collection;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Contracts\Repository\EggVariableRepositoryInterface;

class EggVariableRepository extends EloquentRepository implements EggVariableRepositoryInterface
{
    
    public function model(): string
    {
        return EggVariable::class;
    }

    
    public function getEditableVariables(int $egg): Collection
    {
        return $this->getBuilder()->where([
            ['egg_id', '=', $egg],
            ['user_viewable', '=', 1],
            ['user_editable', '=', 1],
        ])->get($this->getColumns());
    }
}
