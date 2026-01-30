<?php
namespace Pterodactyl\Services\Servers;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Arr;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\User;
use Webmozart\Assert\Assert;
use Pterodactyl\Models\Server;
use Illuminate\Support\Collection;
use Pterodactyl\Models\Allocation;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Models\Objects\DeploymentObject;
use Pterodactyl\Repositories\Eloquent\ServerRepository;
use Pterodactyl\Repositories\Wings\DaemonServerRepository;
use Pterodactyl\Services\Deployment\FindViableNodesService;
use Pterodactyl\Repositories\Eloquent\ServerVariableRepository;
use Pterodactyl\Services\Deployment\AllocationSelectionService;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Illuminate\Support\Facades\Log;
use Exception;
class ServerCreationService
{
    public function __construct(
        private AllocationSelectionService $allocationSelectionService,
        private ConnectionInterface $connection,
        private DaemonServerRepository $daemonServerRepository,
        private FindViableNodesService $findViableNodesService,
        private ServerRepository $repository,
        private ServerDeletionService $serverDeletionService,
        private ServerVariableRepository $serverVariableRepository,
        private VariableValidatorService $validatorService
    ) {
    }
    public function handle(array $data, DeploymentObject $deployment = null): Server
    {
        if ($deployment instanceof DeploymentObject) {
            $allocation = $this->configureDeployment($data, $deployment);
            $data['allocation_id'] = $allocation->id;
            $data['node_id'] = $allocation->node_id;
        }
        if (empty($data['node_id'])) {
            Assert::false(empty($data['allocation_id']), 'Expected a non-empty allocation_id in server creation data.');
            $data['node_id'] = Allocation::query()->findOrFail($data['allocation_id'])->node_id;
        }
        if (empty($data['nest_id'])) {
            Assert::false(empty($data['egg_id']), 'Expected a non-empty egg_id in server creation data.');
            $data['nest_id'] = Egg::query()->findOrFail($data['egg_id'])->nest_id;
        }
        $eggVariableData = $this->validatorService
            ->setUserLevel(User::USER_LEVEL_ADMIN)
            ->handle(Arr::get($data, 'egg_id'), Arr::get($data, 'environment', []));
        $server = $this->connection->transaction(function () use ($data, $eggVariableData) {
            $server = $this->createModel($data);
            $this->storeAssignedAllocations($server, $data);
            $this->storeEggVariables($server, $eggVariableData);
            return $server;
        }, 5);
        try {
            $this->daemonServerRepository->setServer($server)->create(
                Arr::get($data, 'start_on_completion', false) ?? false
            );
        } catch (DaemonConnectionException $exception) {
            try {
                $this->serverDeletionService->withForce()->handle($server);
            } catch (Exception $e) {
                Log::warning('Failed to delete server during creation cleanup', ['exception' => $e]);
            }
            throw $exception;
        }
        return $server;
    }
    private function configureDeployment(array $data, DeploymentObject $deployment): Allocation
    {
        $nodes = $this->findViableNodesService->setLocations($deployment->getLocations())
            ->setDisk(Arr::get($data, 'disk'))
            ->setMemory(Arr::get($data, 'memory'))
            ->handle();
        return $this->allocationSelectionService->setDedicated($deployment->isDedicated())
            ->setNodes($nodes->pluck('id')->toArray())
            ->setPorts($deployment->getPorts())
            ->handle();
    }
    private function createModel(array $data): Server
    {
        $uuid = $this->generateUniqueUuidCombo();
        $model = $this->repository->create([
            'external_id' => Arr::get($data, 'external_id'),
            'uuid' => $uuid,
            'uuidShort' => substr($uuid, 0, 8),
            'node_id' => Arr::get($data, 'node_id'),
            'name' => Arr::get($data, 'name'),
            'description' => Arr::get($data, 'description') ?? '',
            'status' => Server::STATUS_INSTALLING,
            'skip_scripts' => Arr::get($data, 'skip_scripts') ?? isset($data['skip_scripts']),
            'owner_id' => Arr::get($data, 'owner_id'),
            'memory' => Arr::get($data, 'memory'),
            'swap' => Arr::get($data, 'swap'),
            'disk' => Arr::get($data, 'disk'),
            'io' => Arr::get($data, 'io'),
            'cpu' => Arr::get($data, 'cpu'),
            'threads' => Arr::get($data, 'threads'),
            'oom_disabled' => Arr::get($data, 'oom_disabled') ?? true,
            'allocation_id' => Arr::get($data, 'allocation_id'),
            'nest_id' => Arr::get($data, 'nest_id'),
            'egg_id' => Arr::get($data, 'egg_id'),
            'startup' => Arr::get($data, 'startup'),
            'image' => Arr::get($data, 'image'),
            'database_limit' => Arr::get($data, 'database_limit') ?? 0,
            'allocation_limit' => Arr::get($data, 'allocation_limit') ?? 0,
            'backup_limit' => Arr::get($data, 'backup_limit') ?? 0,
            'exp_date' => Arr::get($data, 'exp_date'),
            'masterserver' => Arr::get($data, 'masterserver'),
        ]);
        return $model;
    }
    private function storeAssignedAllocations(Server $server, array $data): void
    {
        $records = [$data['allocation_id']];
        if (isset($data['allocation_additional']) && is_array($data['allocation_additional'])) {
            $records = array_merge($records, $data['allocation_additional']);
        }
        Allocation::query()->whereIn('id', $records)->update([
            'server_id' => $server->id,
        ]);
    }
    private function storeEggVariables(Server $server, Collection $variables): void
    {
        $records = $variables->map(function ($result) use ($server) {
            return [
                'server_id' => $server->id,
                'variable_id' => $result->id,
                'variable_value' => $result->value ?? '',
            ];
        })->toArray();
        if (!empty($records)) {
            $this->serverVariableRepository->insert($records);
        }
    }
    private function generateUniqueUuidCombo(): string
    {
        $uuid = Uuid::uuid4()->toString();
        if (!$this->repository->isUniqueUuidCombo($uuid, substr($uuid, 0, 8))) {
            return $this->generateUniqueUuidCombo();
        }
        return $uuid;
    }
}
