<?php

namespace Pterodactyl\Contracts\Repository;

use Illuminate\Support\Collection;

interface SessionRepositoryInterface extends RepositoryInterface
{
    
    public function getUserSessions(int $user): Collection;

    
    public function deleteUserSession(int $user, string $session): ?int;
}
