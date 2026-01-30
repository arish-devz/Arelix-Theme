<?php

namespace Pterodactyl\Contracts\Repository;

interface SettingsRepositoryInterface extends RepositoryInterface
{
    
    public function set(string $key, string $value = null);

    
    public function get(string $key, mixed $default): mixed;

    
    public function forget(string $key);
}
