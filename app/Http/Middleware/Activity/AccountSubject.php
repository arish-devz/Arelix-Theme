<?php

namespace Pterodactyl\Http\Middleware\Activity;

use Illuminate\Http\Request;
use Pterodactyl\Facades\LogTarget;

class AccountSubject
{
    
    public function handle(Request $request, \Closure $next)
    {
        LogTarget::setActor($request->user());
        LogTarget::setSubject($request->user());

        return $next($request);
    }
}
