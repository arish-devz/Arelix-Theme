<?php

namespace Pterodactyl\Http\Middleware\Activity;

use Illuminate\Http\Request;
use Pterodactyl\Models\Server;
use Pterodactyl\Facades\LogTarget;

class ServerSubject
{
    
    public function handle(Request $request, \Closure $next)
    {
        $server = $request->route()->parameter('server');
        if ($server instanceof Server) {
            LogTarget::setActor($request->user());
            LogTarget::setSubject($server);
        }

        return $next($request);
    }
}
