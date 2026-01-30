<?php

namespace Pterodactyl\Providers;

use Illuminate\Support\ServiceProvider;
use Pterodactyl\Http\ViewComposers\AssetComposer;

class ViewComposerServiceProvider extends ServiceProvider
{
    
    public function boot(): void
    {
        $this->app->make('view')->composer('*', AssetComposer::class);
    }
}
