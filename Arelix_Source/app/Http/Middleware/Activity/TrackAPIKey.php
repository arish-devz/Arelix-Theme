<?php

namespace Pterodactyl\Http\Middleware\Activity;

use Illuminate\Http\Request;
use Pterodactyl\Models\ApiKey;
use Pterodactyl\Facades\LogTarget;

class TrackAPIKey
{
    
    public function handle(Request $request, \Closure $next): mixed
    {
        if ($request->user()) {
            $token = $request->user()->currentAccessToken();

            LogTarget::setApiKeyId($token instanceof ApiKey ? $token->id : null);
        }

        return $next($request);
    }
}
