<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Schedules;

use Pterodactyl\Models\Task;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Models\Permission;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ViewScheduleRequest extends ClientApiRequest
{
    
    public function authorize(): bool
    {
        if (!parent::authorize()) {
            return false;
        }

        $server = $this->route()->parameter('server');
        $schedule = $this->route()->parameter('schedule');

        
        
        if ($server instanceof Server && $schedule instanceof Schedule) {
            $task = $this->route()->parameter('task');

            if ($schedule->server_id !== $server->id || ($task instanceof Task && $task->schedule_id !== $schedule->id)) {
                throw new NotFoundHttpException('The requested resource does not exist on the system.');
            }
        }

        return true;
    }

    public function permission(): string
    {
        return Permission::ACTION_SCHEDULE_READ;
    }
}
