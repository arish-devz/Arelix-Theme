<?php

namespace Pterodactyl\Console\Commands\Schedule;

use Exception;
use Illuminate\Console\Command;
use Pterodactyl\Models\Schedule;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;
use Pterodactyl\Services\Schedules\ProcessScheduleService;

class ProcessRunnableCommand extends Command
{
    protected $signature = 'p:schedule:process';

    protected $description = 'Process schedules in the database and determine which are ready to run.';

    
    public function handle(): int
    {
        $schedules = Schedule::query()
            ->with('tasks')
            ->whereRelation('server', fn (Builder $builder) => $builder->whereNull('status'))
            ->where('is_active', true)
            ->where('is_processing', false)
            ->whereRaw('next_run_at <= NOW()')
            ->get();

        if ($schedules->count() < 1) {
            $this->line('There are no scheduled tasks for servers that need to be run.');

            return 0;
        }

        $bar = $this->output->createProgressBar(count($schedules));
        foreach ($schedules as $schedule) {
            $bar->clear();
            $this->processSchedule($schedule);
            $bar->advance();
            $bar->display();
        }

        $this->line('');

        return 0;
    }

    
    protected function processSchedule(Schedule $schedule)
    {
        if ($schedule->tasks->isEmpty()) {
            return;
        }

        try {
            $this->getLaravel()->make(ProcessScheduleService::class)->handle($schedule);

            $this->line(trans('command/messages.schedule.output_line', [
                'schedule' => $schedule->name,
                'hash' => $schedule->hashid,
            ]));
        } catch (\Throwable|\Exception $exception) {
            Log::error($exception, ['schedule_id' => $schedule->id]);

            $this->error("An error was encountered while processing Schedule #$schedule->id: " . $exception->getMessage());
        }
    }
}
