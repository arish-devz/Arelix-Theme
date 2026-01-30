<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Files;

use Pterodactyl\Models\Server;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class DownloadFileRequest extends ClientApiRequest
{
    
    public function authorize(): bool
    {
        return $this->user()->can('file.read', $this->parameter('server', Server::class));
    }
}
