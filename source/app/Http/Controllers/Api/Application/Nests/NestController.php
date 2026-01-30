<?php

namespace Pterodactyl\Http\Controllers\Api\Application\Nests;

use Pterodactyl\Models\Nest;
use Pterodactyl\Contracts\Repository\NestRepositoryInterface;
use Pterodactyl\Transformers\Api\Application\NestTransformer;
use Pterodactyl\Http\Requests\Api\Application\Nests\GetNestsRequest;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;

class NestController extends ApplicationApiController
{
    
    public function __construct(private NestRepositoryInterface $repository)
    {
        parent::__construct();
    }

    
    public function index(GetNestsRequest $request): array
    {
        $nests = $this->repository->paginated($request->query('per_page') ?? 50);

        return $this->fractal->collection($nests)
            ->transformWith($this->getTransformer(NestTransformer::class))
            ->toArray();
    }

    
    public function view(GetNestsRequest $request, Nest $nest): array
    {
        return $this->fractal->item($nest)
            ->transformWith($this->getTransformer(NestTransformer::class))
            ->toArray();
    }
}
