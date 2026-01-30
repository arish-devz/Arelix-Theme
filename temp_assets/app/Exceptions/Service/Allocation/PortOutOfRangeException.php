<?php

namespace Pterodactyl\Exceptions\Service\Allocation;

use Pterodactyl\Exceptions\DisplayException;

class PortOutOfRangeException extends DisplayException
{
    
    public function __construct()
    {
        parent::__construct(trans('exceptions.allocations.port_out_of_range'));
    }
}
