<?php

namespace Pterodactyl\Contracts\Http;

interface ClientPermissionsRequest
{
    
    public function permission(): string;
}
