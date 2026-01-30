<?php
namespace Pterodactyl\Transformers\Api\Client;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Subuser;
use League\Fractal\Resource\Item;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Permission;
use Illuminate\Container\Container;
use Pterodactyl\Models\EggVariable;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\NullResource;
use Pterodactyl\Services\Servers\StartupCommandService;
class ServerTransformer extends BaseClientTransformer
{
    protected array $defaultIncludes = ['allocations', 'variables'];
    protected array $availableIncludes = ['egg', 'subusers'];
    public function getResourceName(): string
    {
        return Server::RESOURCE_NAME;
    }
    public function transform(Server $server): array
    {
        $service = Container::getInstance()->make(StartupCommandService::class);
        $user = $this->request->user();
        $masterserver = $server->masterserver ?: ($server->subSplit ? $server->subSplit->masterServer->uuid : null);
        
        // Get subdomain for primary allocation
        $primarySubdomain = \Pterodactyl\Models\ServerSubdomain::where('allocation_id', $server->allocation_id)->first();
        
        return [
            'server_owner' => $user->id === $server->owner_id,
            'identifier' => $server->uuidShort,
            'internal_id' => $server->id,
            'uuid' => $server->uuid,
            'name' => $server->name,
            'node' => $server->node->name,
            'is_node_under_maintenance' => $server->node->isUnderMaintenance(),
            'sftp_details' => [
                'ip' => $server->node->sftp_alias ?? $server->node->fqdn,
                'port' => $server->node->daemonSFTP,
            ],
            'description' => $server->description,
            'limits' => [
                'memory' => $server->memory,
                'swap' => $server->swap,
                'disk' => $server->disk,
                'io' => $server->io,
                'cpu' => $server->cpu,
                'threads' => $server->threads,
                'oom_disabled' => $server->oom_disabled,
            ],
            'upload_size' => $server->node->upload_size,
            'invocation' => $service->handle($server, !$user->can(Permission::ACTION_STARTUP_READ, $server)),
            'docker_image' => $server->image,
            'egg_features' => $server->egg->inherit_features,
            'feature_limits' => [
                'databases' => $server->database_limit,
                'allocations' => $server->allocation_limit,
                'backups' => $server->backup_limit,
            ],
            'status' => $server->status,
            'is_suspended' => $server->isSuspended(),
            'is_installing' => !$server->isInstalled(),
            'is_transferring' => !is_null($server->transfer),
            'egg_id' => $server->egg_id,
            'egg_name' => $server->egg->name,
            'nest_id' => $server->nest_id,
            'nest_name' => $server->egg->nest->name,
            'exp_date' => $server->exp_date,
            'masterserver' => $masterserver,
            'primary_subdomain' => $primarySubdomain ? [
                'subdomain' => $primarySubdomain->subdomain,
                'domain' => $primarySubdomain->domain,
                'full_domain' => $primarySubdomain->subdomain . '.' . $primarySubdomain->domain,
                'game_type' => $primarySubdomain->game_type,
            ] : null,
        ];
    }
    public function includeAllocations(Server $server): Collection
    {
        $transformer = $this->makeTransformer(AllocationTransformer::class);
        $user = $this->request->user();
        if (!$user->can(Permission::ACTION_ALLOCATION_READ, $server)) {
            $primary = clone $server->allocation;
            $primary->notes = null;
            return $this->collection([$primary], $transformer, Allocation::RESOURCE_NAME);
        }
        return $this->collection($server->allocations, $transformer, Allocation::RESOURCE_NAME);
    }
    public function includeVariables(Server $server): Collection|NullResource
    {
        if (!$this->request->user()->can(Permission::ACTION_STARTUP_READ, $server)) {
            return $this->null();
        }
        return $this->collection(
            $server->variables->where('user_viewable', true),
            $this->makeTransformer(EggVariableTransformer::class),
            EggVariable::RESOURCE_NAME
        );
    }
    public function includeEgg(Server $server): Item
    {
        return $this->item($server->egg, $this->makeTransformer(EggTransformer::class), Egg::RESOURCE_NAME);
    }
    public function includeSubusers(Server $server): Collection|NullResource
    {
        if (!$this->request->user()->can(Permission::ACTION_USER_READ, $server)) {
            return $this->null();
        }
        return $this->collection($server->subusers, $this->makeTransformer(SubuserTransformer::class), Subuser::RESOURCE_NAME);
    }
}
