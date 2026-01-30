<?php

namespace Pterodactyl\Events\Auth;

use Pterodactyl\Events\Event;
use Illuminate\Queue\SerializesModels;

class FailedPasswordReset extends Event
{
    use SerializesModels;

    
    public function __construct(public string $ip, public string $email)
    {
    }
}
