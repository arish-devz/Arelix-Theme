<?php

namespace Pterodactyl\Extensions\Laravel\Sanctum;

use Pterodactyl\Models\ApiKey;
use Laravel\Sanctum\NewAccessToken as SanctumAccessToken;


class NewAccessToken extends SanctumAccessToken
{
    
    public function __construct(ApiKey $accessToken, string $plainTextToken)
    {
        $this->accessToken = $accessToken;
        $this->plainTextToken = $plainTextToken;
    }
}
