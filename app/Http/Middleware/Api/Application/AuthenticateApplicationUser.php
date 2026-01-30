<?php

namespace Pterodactyl\Http\Middleware\Api\Application;

use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AuthenticateApplicationUser
{
    
    public function handle(Request $request, \Closure $next): mixed
    {
        
        $user = $request->user();
        if (!$user || !$user->root_admin) {
            throw new AccessDeniedHttpException('This account does not have permission to access the API.');
        }

        return $next($request);
    }
}
