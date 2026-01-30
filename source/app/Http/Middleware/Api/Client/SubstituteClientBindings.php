<?php

namespace Pterodactyl\Http\Middleware\Api\Client;

use Pterodactyl\Models\Server;
use Illuminate\Routing\Middleware\SubstituteBindings;

class SubstituteClientBindings extends SubstituteBindings
{
    
    public function handle($request, \Closure $next): mixed
    {
        
        
        $this->router->bind('server', function ($value) {
            return Server::query()->where(strlen($value) === 8 ? 'uuidShort' : 'uuid', $value)->firstOrFail();
        });

        $this->router->bind('user', function ($value, $route) {
            
            $match = $route->parameter('server')
                ->subusers()
                ->whereRelation('user', 'uuid', '=', $value)
                ->firstOrFail();

            return $match->user;
        });

        return parent::handle($request, $next);
    }
}
