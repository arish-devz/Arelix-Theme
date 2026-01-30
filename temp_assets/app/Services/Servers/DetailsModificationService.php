<?php
namespace Pterodactyl\Services\Servers;
use Illuminate\Support\Arr;
use Pterodactyl\Models\Server;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Traits\Services\ReturnsUpdatedModels;
use Pterodactyl\Repositories\Wings\DaemonServerRepository;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
class DetailsModificationService
{
    use ReturnsUpdatedModels;
    public function __construct(private ConnectionInterface $connection, private DaemonServerRepository $serverRepository)
    {
    }
    public function handle(Server $server, array $data): Server
    {
        return $this->connection->transaction(function () use ($data, $server) {
            $owner = $server->owner_id;
            $server->forceFill([
                'external_id' => Arr::get($data, 'external_id'),
                'owner_id' => Arr::get($data, 'owner_id'),
                'name' => Arr::get($data, 'name'),
                'description' => Arr::get($data, 'description') ?? '',
                'exp_date' => Arr::get($data, 'exp_date') ?: null,
                'product_id' => Arr::get($data, 'product_id'),
            ])->saveOrFail();
            if ($server->owner_id !== $owner) {
                try {
                    $this->serverRepository->setServer($server)->revokeUserJTI($owner);
                } catch (DaemonConnectionException $exception) {
                }
            }
            return $server;
        });
    }
}
