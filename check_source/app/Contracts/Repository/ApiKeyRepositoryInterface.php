<?php

namespace Pterodactyl\Contracts\Repository;

use Pterodactyl\Models\User;
use Illuminate\Support\Collection;

interface ApiKeyRepositoryInterface extends RepositoryInterface
{
    
    public function getAccountKeys(User $user): Collection;

    
    public function getApplicationKeys(User $user): Collection;

    
    public function deleteAccountKey(User $user, string $identifier): int;

    
    public function deleteApplicationKey(User $user, string $identifier): int;
}
