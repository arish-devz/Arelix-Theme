<?php

namespace Pterodactyl\Services\Nodes;

use Illuminate\Support\Str;
use Pterodactyl\Models\Node;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Contracts\Encryption\Encrypter;
use Pterodactyl\Repositories\Eloquent\NodeRepository;
use Pterodactyl\Repositories\Wings\DaemonConfigurationRepository;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Exceptions\Service\Node\ConfigurationNotPersistedException;

class NodeUpdateService
{
    
    public function __construct(
        private ConnectionInterface $connection,
        private DaemonConfigurationRepository $configurationRepository,
        private Encrypter $encrypter,
        private NodeRepository $repository
    ) {
    }

    
    public function handle(Node $node, array $data, bool $resetToken = false): Node
    {
        if ($resetToken) {
            $data['daemon_token'] = $this->encrypter->encrypt(Str::random(Node::DAEMON_TOKEN_LENGTH));
            $data['daemon_token_id'] = Str::random(Node::DAEMON_TOKEN_ID_LENGTH);
        }

        [$updated, $exception] = $this->connection->transaction(function () use ($data, $node) {
            
            $updated = $this->repository->withFreshModel()->update($node->id, $data, true, true);

            try {
                
                
                
                
                
                
                
                
                
                $node->fqdn = $updated->fqdn;

                $this->configurationRepository->setNode($node)->update($updated);
            } catch (DaemonConnectionException $exception) {
                Log::warning($exception, ['node_id' => $node->id]);

                
                
                
                
                
                
                
                
                return [$updated, true];
            }

            return [$updated, false];
        });

        if ($exception) {
            throw new ConfigurationNotPersistedException(trans('exceptions.node.daemon_off_config_updated'));
        }

        return $updated;
    }
}
