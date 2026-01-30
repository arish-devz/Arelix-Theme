<?php

namespace Pterodactyl\Events\Subuser;

use Pterodactyl\Events\Event;
use Pterodactyl\Models\Subuser;
use Illuminate\Queue\SerializesModels;

class Deleted extends Event
{
    use SerializesModels;

    
    public function __construct(public Subuser $subuser)
    {
    }
}
