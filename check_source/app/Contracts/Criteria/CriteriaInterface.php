<?php

namespace Pterodactyl\Contracts\Criteria;

use Illuminate\Database\Eloquent\Model;
use Pterodactyl\Repositories\Repository;

interface CriteriaInterface
{
    
    public function apply(Model $model, Repository $repository): mixed;
}
