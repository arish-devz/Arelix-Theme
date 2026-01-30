<?php

namespace Pterodactyl\Traits\Controllers;

use Illuminate\Http\Request;

trait JavascriptInjection
{
    private Request $request;

    
    public function setRequest(Request $request): self
    {
        $this->request = $request;

        return $this;
    }

    
    public function plainInject(array $args = []): string
    {
        return \JavaScript::put($args);
    }
}
