<?php

namespace Pterodactyl\Providers;

use Pterodactyl\Extensions\Hashids;
use Illuminate\Support\ServiceProvider;
use Pterodactyl\Contracts\Extensions\HashidsInterface;

class HashidsServiceProvider extends ServiceProvider
{
    
    public function register(): void
    {
        $this->app->singleton(HashidsInterface::class, function () {
            
            $config = $this->app['config'];

            return new Hashids(
                $config->get('hashids.salt', ''),
                $config->get('hashids.length', 0),
                $config->get('hashids.alphabet', 'abcdefghijkmlnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890')
            );
        });

        $this->app->alias(HashidsInterface::class, 'hashids');
    }
}
