<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Settings;

use Pterodactyl\Models\Server;
use Pterodactyl\Models\Permission;
use Pterodactyl\Contracts\Http\ClientPermissionsRequest;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class RenameServerRequest extends ClientApiRequest implements ClientPermissionsRequest
{
    
    public function permission(): string
    {
        return Permission::ACTION_SETTINGS_RENAME;
    }

    
    public function rules(): array
    {
        return [
            'name' => Server::getRules()['name'],
            'description' => 'string|nullable',
        ];
    }
}
