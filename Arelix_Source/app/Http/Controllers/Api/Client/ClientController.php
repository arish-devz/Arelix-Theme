<?php
namespace Pterodactyl\Http\Controllers\Api\Client;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Permission;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Pterodactyl\Models\Filters\MultiFieldServerFilter;
use Pterodactyl\Transformers\Api\Client\ServerTransformer;
use Pterodactyl\Http\Requests\Api\Client\GetServersRequest;
class ClientController extends ClientApiController
{
    public function __construct()
    {
        parent::__construct();
    }
    public function index(GetServersRequest $request): array
    {
        $user = $request->user();
        $transformer = $this->getTransformer(ServerTransformer::class);
        $builder = QueryBuilder::for(
            Server::query()->leftJoin('nodes', 'servers.node_id', '=', 'nodes.id')->select('servers.*')->with($this->getIncludesForTransformer($transformer, ['node']))
        )->allowedFilters([
            'uuid',
            'name',
            'description',
            'external_id',
            AllowedFilter::custom('*', new MultiFieldServerFilter()),
        ])->allowedSorts(['name', 'nest_id', 'egg_id', AllowedSort::field('node', 'nodes.name')]);
        $type = $request->input('type');
        if (in_array($type, ['admin', 'admin-all'])) {
            if (!$user->root_admin && !$user->hasAdminPermission('arelix.global.view_all_servers')) {
                $builder->whereRaw('1 = 2');
            } else {
                $builder = $type === 'admin-all'
                    ? $builder
                    : $builder->whereNotIn('servers.id', $user->accessibleServers()->pluck('id')->all());
            }
        } elseif ($type === 'owner') {
            $builder = $builder->where('servers.owner_id', $user->id);
        } else {
            $builder = $builder->whereIn('servers.id', $user->accessibleServers()->pluck('id')->all());
        }
        $servers = $builder->paginate(min($request->query('per_page', 50), 5000))->appends($request->query());
        return $this->fractal->transformWith($transformer)->collection($servers)->toArray();
    }
    public function permissions(): array
    {
        return [
            'object' => 'system_permissions',
            'attributes' => [
                'permissions' => Permission::permissions(),
            ],
        ];
    }
}
