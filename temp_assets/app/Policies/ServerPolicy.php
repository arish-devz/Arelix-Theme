<?php

namespace Pterodactyl\Policies;

use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;

class ServerPolicy
{
    
    protected function checkPermission(User $user, Server $server, string $permission): bool
    {
        $subuser = $server->subusers->where('user_id', $user->id)->first();
        if ($subuser && in_array($permission, $subuser->permissions)) {
            return true;
        }

        if ($user->permissionRole && $user->permissionRole->hasPermission($permission)) {
            return true;
        }
        return false;
    }

    
    public function before(User $user, string $ability, Server $server): bool
    {
        if ($user->root_admin || $server->owner_id === $user->id) {
            return true;
        }

        return $this->checkPermission($user, $server, $ability);
    }

    
    public function __call(string $name, mixed $arguments)
    {
        
    }
}
