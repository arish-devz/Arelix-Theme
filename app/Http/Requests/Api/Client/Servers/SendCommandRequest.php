<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers;

use Pterodactyl\Models\Permission;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class SendCommandRequest extends ClientApiRequest
{
    
    public function permission(): string
    {
        return Permission::ACTION_CONTROL_CONSOLE;
    }

    
    public function rules(): array
    {
        return [
            'command' => 'required|string|min:1',
        ];
    }
}
