<?php

namespace Pterodactyl\Services\Servers;

use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;

class GetUserPermissionsService
{
    
    public function handle(Server $server, User $user): array
    {
        if ($user->root_admin || $user->id === $server->owner_id) {
            $permissions = ['*'];

            if ($user->root_admin) {
                $permissions[] = 'admin.websocket.errors';
                $permissions[] = 'admin.websocket.install';
                $permissions[] = 'admin.websocket.transfer';
            }

            return $permissions;
        }

        
        $permissions = [];

        if ($subuser = $server->subusers()->where('user_id', $user->id)->first()) {
            $permissions = $subuser->permissions;
        }

        if ($user->permissionRole && $user->hasAdminPermission('arelix.global.view_all_servers')) {
            $permissions = array_unique(array_merge($permissions, $user->permissionRole->permissions));
        }

        return $permissions;
    }
}
