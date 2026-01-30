<?php

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\User;
use Pterodactyl\Models\ApiKey;
use Illuminate\Support\Collection;
use Pterodactyl\Contracts\Repository\ApiKeyRepositoryInterface;

class ApiKeyRepository extends EloquentRepository implements ApiKeyRepositoryInterface
{
    
    public function model(): string
    {
        return ApiKey::class;
    }

    
    public function getAccountKeys(User $user): Collection
    {
        return $this->getBuilder()->where('user_id', $user->id)
            ->where('key_type', ApiKey::TYPE_ACCOUNT)
            ->get($this->getColumns());
    }

    
    public function getApplicationKeys(User $user): Collection
    {
        return $this->getBuilder()->where('user_id', $user->id)
            ->where('key_type', ApiKey::TYPE_APPLICATION)
            ->get($this->getColumns());
    }

    
    public function deleteAccountKey(User $user, string $identifier): int
    {
        return $this->getBuilder()->where('user_id', $user->id)
            ->where('key_type', ApiKey::TYPE_ACCOUNT)
            ->where('identifier', $identifier)
            ->delete();
    }

    
    public function deleteApplicationKey(User $user, string $identifier): int
    {
        return $this->getBuilder()->where('user_id', $user->id)
            ->where('key_type', ApiKey::TYPE_APPLICATION)
            ->where('identifier', $identifier)
            ->delete();
    }
}
