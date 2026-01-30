<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Illuminate\Http\Request;
use Pterodactyl\Models\Server;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Models\Permission;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Repositories\Eloquent\SubuserRepository;
use Pterodactyl\Services\Subusers\SubuserCreationService;
use Pterodactyl\Repositories\Wings\DaemonServerRepository;
use Pterodactyl\Transformers\Api\Client\SubuserTransformer;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Http\Requests\Api\Client\Servers\Subusers\GetSubuserRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Subusers\StoreSubuserRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Subusers\DeleteSubuserRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Subusers\UpdateSubuserRequest;

class SubuserController extends ClientApiController
{
    
    public function __construct(
        private SubuserRepository $repository,
        private SubuserCreationService $creationService,
        private DaemonServerRepository $serverRepository
    ) {
        parent::__construct();
    }

    
    public function index(GetSubuserRequest $request, Server $server): array
    {
        return $this->fractal->collection($server->subusers)
            ->transformWith($this->getTransformer(SubuserTransformer::class))
            ->toArray();
    }

    
    public function view(GetSubuserRequest $request): array
    {
        $subuser = $request->attributes->get('subuser');

        return $this->fractal->item($subuser)
            ->transformWith($this->getTransformer(SubuserTransformer::class))
            ->toArray();
    }

    
    public function store(StoreSubuserRequest $request, Server $server): array
    {
        $response = $this->creationService->handle(
            $server,
            $request->input('email'),
            $this->getDefaultPermissions($request)
        );

        Activity::event('server:subuser.create')
            ->subject($response->user)
            ->property(['email' => $request->input('email'), 'permissions' => $this->getDefaultPermissions($request)])
            ->log();

        return $this->fractal->item($response)
            ->transformWith($this->getTransformer(SubuserTransformer::class))
            ->toArray();
    }

    
    public function update(UpdateSubuserRequest $request, Server $server): array
    {
        
        $subuser = $request->attributes->get('subuser');

        $permissions = $this->getDefaultPermissions($request);
        $current = $subuser->permissions;

        sort($permissions);
        sort($current);

        $log = Activity::event('server:subuser.update')
            ->subject($subuser->user)
            ->property([
                'email' => $subuser->user->email,
                'old' => $current,
                'new' => $permissions,
                'revoked' => true,
            ]);

        
        
        if ($permissions !== $current) {
            $log->transaction(function ($instance) use ($request, $subuser, $server) {
                $this->repository->update($subuser->id, [
                    'permissions' => $this->getDefaultPermissions($request),
                ]);

                try {
                    $this->serverRepository->setServer($server)->revokeUserJTI($subuser->user_id);
                } catch (DaemonConnectionException $exception) {
                    
                    
                    Log::warning($exception, ['user_id' => $subuser->user_id, 'server_id' => $server->id]);

                    $instance->property('revoked', false);
                }
            });
        }

        $log->reset();

        return $this->fractal->item($subuser->refresh())
            ->transformWith($this->getTransformer(SubuserTransformer::class))
            ->toArray();
    }

    
    public function delete(DeleteSubuserRequest $request, Server $server): JsonResponse
    {
        
        $subuser = $request->attributes->get('subuser');

        $log = Activity::event('server:subuser.delete')
            ->subject($subuser->user)
            ->property('email', $subuser->user->email)
            ->property('revoked', true);

        $log->transaction(function ($instance) use ($server, $subuser) {
            $subuser->delete();

            try {
                $this->serverRepository->setServer($server)->revokeUserJTI($subuser->user_id);
            } catch (DaemonConnectionException $exception) {
                
                Log::warning($exception, ['user_id' => $subuser->user_id, 'server_id' => $server->id]);

                $instance->property('revoked', false);
            }
        });

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }

    
    protected function getDefaultPermissions(Request $request): array
    {
        $allowed = Permission::permissions()
            ->map(function ($value, $prefix) {
                return array_map(function ($value) use ($prefix) {
                    return "$prefix.$value";
                }, array_keys($value['keys']));
            })
            ->flatten()
            ->all();

        $cleaned = array_intersect($request->input('permissions') ?? [], $allowed);

        return array_unique(array_merge($cleaned, [Permission::ACTION_WEBSOCKET_CONNECT]));
    }
}
