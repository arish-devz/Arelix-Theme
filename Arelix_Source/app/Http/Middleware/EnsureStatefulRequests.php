<?php

namespace Pterodactyl\Http\Middleware;

use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

class EnsureStatefulRequests extends EnsureFrontendRequestsAreStateful
{
    
    public static function fromFrontend($request)
    {
        if (parent::fromFrontend($request)) {
            return true;
        }

        return $request->hasCookie(config('session.cookie'));
    }
}
