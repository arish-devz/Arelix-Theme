<?php

namespace Pterodactyl\Contracts\Repository;

use Pterodactyl\Models\Schedule;
use Illuminate\Support\Collection;

interface ScheduleRepositoryInterface extends RepositoryInterface
{
    
    public function findServerSchedules(int $server): Collection;

    
    public function getScheduleWithTasks(int $schedule): Schedule;
}
