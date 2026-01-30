<?php

namespace Pterodactyl\Contracts\Extensions;

use Hashids\HashidsInterface as VendorHashidsInterface;

interface HashidsInterface extends VendorHashidsInterface
{
    
    public function decodeFirst(string $encoded, string $default = null): mixed;
}
