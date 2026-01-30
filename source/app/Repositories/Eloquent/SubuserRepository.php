<?php

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\Subuser;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Pterodactyl\Contracts\Repository\SubuserRepositoryInterface;

class SubuserRepository extends EloquentRepository implements SubuserRepositoryInterface
{
    
    public function model(): string
    {
        return Subuser::class;
    }

    
    public function loadServerAndUserRelations(Subuser $subuser, bool $refresh = false): Subuser
    {
        if (!$subuser->relationLoaded('server') || $refresh) {
            $subuser->load('server');
        }

        if (!$subuser->relationLoaded('user') || $refresh) {
            $subuser->load('user');
        }

        return $subuser;
    }

    
    public function getWithPermissions(Subuser $subuser, bool $refresh = false): Subuser
    {
        if (!$subuser->relationLoaded('permissions') || $refresh) {
            $subuser->load('permissions');
        }

        if (!$subuser->relationLoaded('user') || $refresh) {
            $subuser->load('user');
        }

        return $subuser;
    }

    
    public function getWithPermissionsUsingUserAndServer(int $user, int $server): Subuser
    {
        $instance = $this->getBuilder()->with('permissions')->where([
            ['user_id', '=', $user],
            ['server_id', '=', $server],
        ])->first();

        if (is_null($instance)) {
            throw new RecordNotFoundException();
        }

        return $instance;
    }
}
