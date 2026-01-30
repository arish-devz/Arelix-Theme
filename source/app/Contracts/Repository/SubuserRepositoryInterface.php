<?php

namespace Pterodactyl\Contracts\Repository;

use Pterodactyl\Models\Subuser;

interface SubuserRepositoryInterface extends RepositoryInterface
{
    
    public function loadServerAndUserRelations(Subuser $subuser, bool $refresh = false): Subuser;

    
    public function getWithPermissions(Subuser $subuser, bool $refresh = false): Subuser;

    
    public function getWithPermissionsUsingUserAndServer(int $user, int $server): Subuser;
}
