<?php

namespace Pterodactyl\Contracts\Core;

use Pterodactyl\Events\Event;

interface ReceivesEvents
{
    
    public function handle(Event $notification): void;
}
