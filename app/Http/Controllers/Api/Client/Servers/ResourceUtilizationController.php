<?php
namespace Pterodactyl\Http\Controllers\Api\Client\Servers;
use Carbon\Carbon;
use Pterodactyl\Models\Server;
use Illuminate\Cache\Repository;
use Pterodactyl\Transformers\Api\Client\StatsTransformer;
use Pterodactyl\Repositories\Wings\DaemonServerRepository;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Servers\GetServerRequest;
class ResourceUtilizationController extends ClientApiController
{
    public function __construct(private Repository $cache, private DaemonServerRepository $repository)
    {
        parent::__construct();
    }
    public function __invoke(GetServerRequest $request, Server $server): array
    {
        $key = "resources:$server->uuid";
        $stats = $this->cache->remember($key, Carbon::now()->addSeconds(1), function () use ($server) {
            return $this->repository->setServer($server)->getDetails();
        });
        return $this->fractal->item($stats)
            ->transformWith($this->getTransformer(StatsTransformer::class))
            ->toArray();
    }
}
