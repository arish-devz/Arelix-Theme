<?php

namespace Pterodactyl\Http\Requests\Api\Client;

use Pterodactyl\Models\Server;
use Pterodactyl\Contracts\Http\ClientPermissionsRequest;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;


class ClientApiRequest extends ApplicationApiRequest
{
    
    public function authorize(): bool
    {
        if ($this instanceof ClientPermissionsRequest || method_exists($this, 'permission')) {
            $server = $this->route()->parameter('server');

            if ($server instanceof Server) {
                return $this->user()->can($this->permission(), $server);
            }

            
            
            return false;
        }

        return true;
    }
}
