<?php

namespace Pterodactyl\Http\Controllers\Api\Application\Servers;

use Illuminate\Http\Response;
use Pterodactyl\Models\Server;
use Illuminate\Http\JsonResponse;
use Spatie\QueryBuilder\QueryBuilder;
use Pterodactyl\Services\Servers\ServerCreationService;
use Pterodactyl\Services\Servers\ServerDeletionService;
use Pterodactyl\Transformers\Api\Application\ServerTransformer;
use Pterodactyl\Http\Requests\Api\Application\Servers\GetServerRequest;
use Pterodactyl\Http\Requests\Api\Application\Servers\GetServersRequest;
use Pterodactyl\Http\Requests\Api\Application\Servers\ServerWriteRequest;
use Pterodactyl\Http\Requests\Api\Application\Servers\StoreServerRequest;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;

class ServerController extends ApplicationApiController
{
    
    public function __construct(
        private ServerCreationService $creationService,
        private ServerDeletionService $deletionService
    ) {
        parent::__construct();
    }

    
    public function index(GetServersRequest $request): array
    {
        $servers = QueryBuilder::for(Server::query())
            ->allowedFilters(['uuid', 'uuidShort', 'name', 'description', 'image', 'external_id'])
            ->allowedSorts(['id', 'uuid'])
            ->paginate($request->query('per_page') ?? 50);

        return $this->fractal->collection($servers)
            ->transformWith($this->getTransformer(ServerTransformer::class))
            ->toArray();
    }

    
    public function store(StoreServerRequest $request): JsonResponse
    {
        $server = $this->creationService->handle($request->validated(), $request->getDeploymentObject());

        return $this->fractal->item($server)
            ->transformWith($this->getTransformer(ServerTransformer::class))
            ->respond(201);
    }

    
    public function view(GetServerRequest $request, Server $server): array
    {
        return $this->fractal->item($server)
            ->transformWith($this->getTransformer(ServerTransformer::class))
            ->toArray();
    }

    
    public function delete(ServerWriteRequest $request, Server $server, string $force = ''): Response
    {
        $this->deletionService->withForce($force === 'force')->handle($server);

        return $this->returnNoContent();
    }
}
