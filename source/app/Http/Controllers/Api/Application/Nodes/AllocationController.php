<?php

namespace Pterodactyl\Http\Controllers\Api\Application\Nodes;

use Pterodactyl\Models\Node;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Models\Allocation;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Illuminate\Database\Eloquent\Builder;
use Pterodactyl\Services\Allocations\AssignmentService;
use Pterodactyl\Services\Allocations\AllocationDeletionService;
use Pterodactyl\Transformers\Api\Application\AllocationTransformer;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;
use Pterodactyl\Http\Requests\Api\Application\Allocations\GetAllocationsRequest;
use Pterodactyl\Http\Requests\Api\Application\Allocations\StoreAllocationRequest;
use Pterodactyl\Http\Requests\Api\Application\Allocations\DeleteAllocationRequest;

class AllocationController extends ApplicationApiController
{
    
    public function __construct(
        private AssignmentService $assignmentService,
        private AllocationDeletionService $deletionService
    ) {
        parent::__construct();
    }

    
    public function index(GetAllocationsRequest $request, Node $node): array
    {
        $allocations = QueryBuilder::for($node->allocations())
            ->allowedFilters([
                AllowedFilter::exact('ip'),
                AllowedFilter::exact('port'),
                'ip_alias',
                AllowedFilter::callback('server_id', function (Builder $builder, $value) {
                    if (empty($value) || is_bool($value) || !ctype_digit((string) $value)) {
                        return $builder->whereNull('server_id');
                    }

                    return $builder->where('server_id', $value);
                }),
            ])
            ->paginate($request->query('per_page') ?? 50);

        return $this->fractal->collection($allocations)
            ->transformWith($this->getTransformer(AllocationTransformer::class))
            ->toArray();
    }

    
    public function store(StoreAllocationRequest $request, Node $node): JsonResponse
    {
        $this->assignmentService->handle($node, $request->validated());

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }

    
    public function delete(DeleteAllocationRequest $request, Node $node, Allocation $allocation): JsonResponse
    {
        $this->deletionService->handle($allocation);

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }
}
