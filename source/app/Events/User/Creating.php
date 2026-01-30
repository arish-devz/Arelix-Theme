<?php

namespace Pterodactyl\Events\User;

use Pterodactyl\Models\User;
use Pterodactyl\Events\Event;
use Illuminate\Queue\SerializesModels;

class Creating extends Event
{
    use SerializesModels;

    
    public function __construct(public User $user)
    {
    }
}
