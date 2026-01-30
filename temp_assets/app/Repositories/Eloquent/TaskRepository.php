<?php

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\Task;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Pterodactyl\Contracts\Repository\TaskRepositoryInterface;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;

class TaskRepository extends EloquentRepository implements TaskRepositoryInterface
{
    
    public function model(): string
    {
        return Task::class;
    }

    
    public function getTaskForJobProcess(int $id): Task
    {
        try {
            return $this->getBuilder()->with('server.user', 'schedule')->findOrFail($id, $this->getColumns());
        } catch (ModelNotFoundException) {
            throw new RecordNotFoundException();
        }
    }

    
    public function getNextTask(int $schedule, int $index): ?Task
    {
        return $this->getBuilder()->where('schedule_id', '=', $schedule)
            ->orderBy('sequence_id')
            ->where('sequence_id', '>', $index)
            ->first($this->getColumns());
    }
}
