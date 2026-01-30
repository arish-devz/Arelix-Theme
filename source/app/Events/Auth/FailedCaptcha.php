<?php
namespace Pterodactyl\Events\Auth;
use Pterodactyl\Events\Event;
use Illuminate\Queue\SerializesModels;
class FailedCaptcha extends Event
{
    use SerializesModels;
    public function __construct(public string $ip, public ?string $domain = null)
    {
    }
}
