<?php

namespace Pterodactyl\Exceptions\Service\Database;

use Pterodactyl\Exceptions\DisplayException;

class NoSuitableDatabaseHostException extends DisplayException
{
    
    public function __construct()
    {
        parent::__construct('No database host was found that meets the requirements for this server.');
    }
}
