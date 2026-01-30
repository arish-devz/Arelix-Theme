<?php

namespace Pterodactyl\Contracts\Repository;

use Pterodactyl\Models\Task;

interface TaskRepositoryInterface extends RepositoryInterface
{
    
    public function getTaskForJobProcess(int $id): Task;

    
    public function getNextTask(int $schedule, int $index): ?Task;
}
