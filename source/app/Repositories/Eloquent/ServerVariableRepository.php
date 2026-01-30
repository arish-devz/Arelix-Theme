<?php

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\ServerVariable;
use Pterodactyl\Contracts\Repository\ServerVariableRepositoryInterface;

class ServerVariableRepository extends EloquentRepository implements ServerVariableRepositoryInterface
{
    
    public function model(): string
    {
        return ServerVariable::class;
    }
}
