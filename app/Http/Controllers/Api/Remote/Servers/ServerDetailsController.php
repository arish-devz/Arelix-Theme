<?php

namespace Pterodactyl\Http\Controllers\Api\Remote\Servers;

use Illuminate\Http\Request;
use Pterodactyl\Models\Server;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Facades\Activity;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Eggs\EggConfigurationService;
use Pterodactyl\Repositories\Eloquent\ServerRepository;
use Pterodactyl\Http\Resources\Wings\ServerConfigurationCollection;
use Pterodactyl\Services\Servers\ServerConfigurationStructureService;

class ServerDetailsController extends Controller
{
    
    public function __construct(
        protected ConnectionInterface $connection,
        private ServerRepository $repository,
        private ServerConfigurationStructureService $configurationStructureService,
        private EggConfigurationService $eggConfigurationService
    ) {
    }

    
    public function __invoke(Request $request, string $uuid): JsonResponse
    {
        $server = $this->repository->getByUuid($uuid);

        return new JsonResponse([
            'settings' => $this->configurationStructureService->handle($server),
            'process_configuration' => $this->eggConfigurationService->handle($server),
        ]);
    }

    
    public function list(Request $request): ServerConfigurationCollection
    {
        
        $node = $request->attributes->get('node');

        
        
        $servers = Server::query()->with('allocations', 'egg', 'mounts', 'variables', 'location')
            ->where('node_id', $node->id)
            
            
            ->paginate((int) $request->input('per_page', 50));

        return new ServerConfigurationCollection($servers);
    }

    
    public function resetState(Request $request): JsonResponse
    {
        $node = $request->attributes->get('node');

        
        
        
        
        
        
        $servers = Server::query()
            ->with([
                'activity' => fn ($builder) => $builder
                    ->where('activity_logs.event', 'server:backup.restore-started')
                    ->latest('timestamp'),
            ])
            ->where('node_id', $node->id)
            ->where('status', Server::STATUS_RESTORING_BACKUP)
            ->get();

        $this->connection->transaction(function () use ($node, $servers) {
            
            foreach ($servers as $server) {
                
                $activity = $server->activity->first();
                if (!is_null($activity)) {
                    if ($subject = $activity->subjects->where('subject_type', 'backup')->first()) {
                        
                        
                        Activity::event('server:backup.restore-failed')
                            ->subject($server, $subject->subject)
                            ->property('name', $subject->subject->name)
                            ->log();
                    }
                }
            }

            
            
            Server::query()->where('node_id', $node->id)
                ->whereIn('status', [Server::STATUS_INSTALLING, Server::STATUS_RESTORING_BACKUP])
                ->update(['status' => null]);
        });

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }
}
