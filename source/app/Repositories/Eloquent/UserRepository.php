<?php

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\User;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;

class UserRepository extends EloquentRepository implements UserRepositoryInterface
{
    
    public function model(): string
    {
        return User::class;
    }
}
