<?php

namespace Pterodactyl\Traits\Controllers;

use JavaScript;

trait PlainJavascriptInjection
{
    
    public function injectJavascript($data)
    {
        \JavaScript::put($data);
    }
}
