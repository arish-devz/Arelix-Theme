<?php
namespace Pterodactyl\Services\Servers;
use Illuminate\Support\Arr;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Allocation;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Exceptions\DisplayException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Pterodactyl\Repositories\Wings\DaemonServerRepository;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
class BuildModificationService
{
    public function __construct(
        private ConnectionInterface $connection,
        private DaemonServerRepository $daemonServerRepository,
        private ServerConfigurationStructureService $structureService
    ) {
    }
    public function handle(Server $server, array $data): Server
    {
        $server = $this->connection->transaction(function () use ($server, $data) {
            $this->processAllocations($server, $data);
            if (isset($data['allocation_id']) && $data['allocation_id'] != $server->allocation_id) {
                try {
                    Allocation::query()->where('id', $data['allocation_id'])->where('server_id', $server->id)->firstOrFail();
                } catch (ModelNotFoundException) {
                    throw new DisplayException('The requested default allocation is not currently assigned to this server.');
                }
            }
            $merge = Arr::only($data, ['oom_disabled', 'memory', 'swap', 'io', 'cpu', 'threads', 'disk', 'allocation_id']);
            if (array_key_exists('database_limit', $data)) {
                $merge['database_limit'] = $data['database_limit'] ?? null;
            }
            if (array_key_exists('allocation_limit', $data)) {
                $merge['allocation_limit'] = $data['allocation_limit'] ?? null;
            }
            if (array_key_exists('backup_limit', $data)) {
                $merge['backup_limit'] = $data['backup_limit'] ?? 0;
            }
            $server->forceFill($merge)->saveOrFail();
            return $server->refresh();
        });
        $updateData = $this->structureService->handle($server);
        if (!empty($updateData['build'])) {
            try {
                $this->daemonServerRepository->setServer($server)->sync();
            } catch (DaemonConnectionException $exception) {
                Log::warning($exception, ['server_id' => $server->id]);
            }
        }
        return $server;
    }
    private function processAllocations(Server $server, array &$data): void
    {
        if (empty($data['add_allocations']) && empty($data['remove_allocations'])) {
            return;
        }
        if (!empty($data['add_allocations'])) {
            $query = Allocation::query()
                ->where('node_id', $server->node_id)
                ->whereIn('id', $data['add_allocations'])
                ->whereNull('server_id');
            $freshlyAllocated = $query->pluck('id')->first();
            $query->update(['server_id' => $server->id, 'notes' => null]);
        }
        if (!empty($data['remove_allocations'])) {
            foreach ($data['remove_allocations'] as $allocation) {
                if ($allocation === ($data['allocation_id'] ?? $server->allocation_id)) {
                    if (empty($freshlyAllocated)) {
                        throw new DisplayException('You are attempting to delete the default allocation for this server but there is no fallback allocation to use.');
                    }
                    $data['allocation_id'] = $freshlyAllocated;
                }
            }
            Allocation::query()->where('node_id', $server->node_id)
                ->where('server_id', $server->id)
                ->whereIn('id', array_diff($data['remove_allocations'], $data['add_allocations'] ?? []))
                ->update([
                    'notes' => null,
                    'server_id' => null,
                ]);
        }
    }
}
