<?php

namespace Pterodactyl\Exceptions\Service;

use Pterodactyl\Exceptions\DisplayException;

class ServiceLimitExceededException extends DisplayException
{
    
    public function __construct(string $message, \Throwable $previous = null)
    {
        parent::__construct($message, $previous, self::LEVEL_WARNING);
    }
}
