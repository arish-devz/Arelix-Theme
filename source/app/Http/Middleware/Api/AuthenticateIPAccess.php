<?php

namespace Pterodactyl\Http\Middleware\Api;

use IPTools\IP;
use IPTools\Range;
use Illuminate\Http\Request;
use Pterodactyl\Facades\Activity;
use Laravel\Sanctum\TransientToken;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AuthenticateIPAccess
{
    
    public function handle(Request $request, \Closure $next): mixed
    {
        
        $token = $request->user()->currentAccessToken();

        
        
        
        
        if ($token instanceof TransientToken || empty($token->allowed_ips)) {
            return $next($request);
        }

        $find = new IP($request->ip());
        foreach ($token->allowed_ips as $ip) {
            if (Range::parse($ip)->contains($find)) {
                return $next($request);
            }
        }

        Activity::event('auth:ip-blocked')
            ->actor($request->user())
            ->subject($request->user(), $token)
            ->property('identifier', $token->identifier)
            ->log();

        throw new AccessDeniedHttpException('This IP address (' . $request->ip() . ') does not have permission to access the API using these credentials.');
    }
}
