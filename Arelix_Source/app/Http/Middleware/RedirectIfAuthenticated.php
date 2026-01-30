<?php

namespace Pterodactyl\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Auth\AuthManager;

class RedirectIfAuthenticated
{
    
    public function __construct(private AuthManager $authManager)
    {
    }

    
    public function handle(Request $request, \Closure $next, string $guard = null): mixed
    {
        if ($this->authManager->guard($guard)->check()) {
            return redirect()->route('index');
        }

        return $next($request);
    }
}
