<?php
namespace Pterodactyl\Http\Middleware\Api\Client\Server;
use Illuminate\Http\Request;
use Pterodactyl\Models\Task;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Subuser;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\ServerImport;
use Pterodactyl\Models\ServerSplit;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
class ResourceBelongsToServer
{
    public function handle(Request $request, \Closure $next): mixed
    {
        $params = $request->route()->parameters();
        if (is_null($params) || !$params['server'] instanceof Server) {
            throw new \InvalidArgumentException('This middleware cannot be used in a context that is missing a server in the parameters.');
        }
        $server = $request->route()->parameter('server');
        $exception = new NotFoundHttpException('The requested resource was not found for this server.');
        foreach ($params as $key => $model) {
            if ($key === 'server' || !$model instanceof Model) {
                continue;
            }
            switch (get_class($model)) {
                case Allocation::class:
                case Backup::class:
                case Database::class:
                case Schedule::class:
                case Subuser::class:
                case ServerImport::class:
                    if ($model->server_id !== $server->id) {
                        throw $exception;
                    }
                    break;
                case ServerSplit::class:
                    if ($model->master_server_id !== $server->id) {
                        throw $exception;
                    }
                    break;
                case \Pterodactyl\Models\ReverseProxy::class:
                    if ($model->server_id !== $server->id) {
                         throw $exception;
                    }
                    break;
                case User::class:
                    $subuser = $server->subusers()->where('user_id', $model->id)->first();
                    if (is_null($subuser)) {
                        throw $exception;
                    }
                    $request->attributes->set('subuser', $subuser);
                    break;
                case Task::class:
                    $schedule = $request->route()->parameter('schedule');
                    if ($model->schedule_id !== $schedule->id || $schedule->server_id !== $server->id) {
                        throw $exception;
                    }
                    break;
                default:
                    throw new \InvalidArgumentException('There is no handler configured for a resource of this type: ' . get_class($model));
            }
        }
        return $next($request);
    }
}
