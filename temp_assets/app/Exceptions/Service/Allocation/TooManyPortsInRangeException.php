<?php

namespace Pterodactyl\Exceptions\Service\Allocation;

use Pterodactyl\Exceptions\DisplayException;

class TooManyPortsInRangeException extends DisplayException
{
    
    public function __construct()
    {
        parent::__construct(trans('exceptions.allocations.too_many_ports'));
    }
}
