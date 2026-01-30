<?php

namespace Pterodactyl\Observers;

use Pterodactyl\Events;
use Pterodactyl\Models\User;

class UserObserver
{
    protected string $uuid;

    
    public function creating(User $user): void
    {
        event(new Events\User\Creating($user));
    }

    
    public function created(User $user): void
    {
        event(new Events\User\Created($user));
    }

    
    public function deleting(User $user): void
    {
        event(new Events\User\Deleting($user));
    }

    
    public function deleted(User $user): void
    {
        event(new Events\User\Deleted($user));
    }
}
