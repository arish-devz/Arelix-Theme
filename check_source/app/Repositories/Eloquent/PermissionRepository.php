<?php

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Contracts\Repository\PermissionRepositoryInterface;
use Exception;

class PermissionRepository extends EloquentRepository implements PermissionRepositoryInterface
{
    
    public function model(): string
    {
        throw new Exception('This functionality is not implemented.');
    }
}
