<?php

namespace Pterodactyl\Services\Api;

use Pterodactyl\Models\ApiKey;
use Illuminate\Contracts\Encryption\Encrypter;
use Pterodactyl\Contracts\Repository\ApiKeyRepositoryInterface;

class KeyCreationService
{
    private int $keyType = ApiKey::TYPE_NONE;

    
    public function __construct(private ApiKeyRepositoryInterface $repository, private Encrypter $encrypter)
    {
    }

    
    public function setKeyType(int $type): self
    {
        $this->keyType = $type;

        return $this;
    }

    
    public function handle(array $data, array $permissions = []): ApiKey
    {
        $data = array_merge($data, [
            'key_type' => $this->keyType,
            'identifier' => ApiKey::generateTokenIdentifier($this->keyType),
            'token' => $this->encrypter->encrypt(str_random(ApiKey::KEY_LENGTH)),
        ]);

        if ($this->keyType === ApiKey::TYPE_APPLICATION) {
            $data = array_merge($data, $permissions);
        }

        return $this->repository->create($data, true, true);
    }
}
