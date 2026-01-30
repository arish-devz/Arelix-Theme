<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Subusers;

use Pterodactyl\Models\Permission;

class GetSubuserRequest extends SubuserRequest
{
    
    public function permission(): string
    {
        return Permission::ACTION_USER_READ;
    }
}
