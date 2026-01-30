<?php

namespace Pterodactyl\Services\Nodes;

use Ramsey\Uuid\Uuid;
use Illuminate\Support\Str;
use Pterodactyl\Models\Node;
use Illuminate\Contracts\Encryption\Encrypter;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;

class NodeCreationService
{
    
    public function __construct(protected NodeRepositoryInterface $repository)
    {
    }

    
    public function handle(array $data): Node
    {
        $data['uuid'] = Uuid::uuid4()->toString();
        $data['daemon_token'] = app(Encrypter::class)->encrypt(Str::random(Node::DAEMON_TOKEN_LENGTH));
        $data['daemon_token_id'] = Str::random(Node::DAEMON_TOKEN_ID_LENGTH);

        return $this->repository->create($data, true, true);
    }
}
