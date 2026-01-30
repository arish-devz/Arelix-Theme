<?php

namespace Pterodactyl\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class RemovedFromServer extends Notification implements ShouldQueue
{
    use Queueable;

    public object $server;

    
    public function __construct(array $server)
    {
        $this->server = (object) $server;
    }

    
    public function via(): array
    {
        return ['mail'];
    }

    
    public function toMail(): MailMessage
    {
        return (new MailMessage())
            ->error()
            ->greeting('Hello ' . $this->server->user . '.')
            ->line('You have been removed as a subuser for the following server.')
            ->line('Server Name: ' . $this->server->name)
            ->action('Visit Panel', route('index'));
    }
}
