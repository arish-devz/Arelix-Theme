<?php
namespace Pterodactyl\Console;
use Ramsey\Uuid\Uuid;
use Pterodactyl\Models\ActivityLog;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Console\PruneCommand;
use Pterodactyl\Repositories\Eloquent\SettingsRepository;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Pterodactyl\Services\Telemetry\TelemetryCollectionService;
use Pterodactyl\Console\Commands\Schedule\ProcessRunnableCommand;
use Pterodactyl\Console\Commands\Maintenance\PruneOrphanedBackupsCommand;
use Pterodactyl\Console\Commands\Maintenance\CleanServiceBackupFilesCommand;
use Pterodactyl\Console\Commands\Maintenance\CleanTempDatabaseExportsCommand;
use Pterodactyl\Console\Commands\Maintenance\CleanRecycleBinCommand;
class Kernel extends ConsoleKernel
{
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
    }
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('cache:prune-stale-tags')->hourly();
        $schedule->command(ProcessRunnableCommand::class)->everyMinute()->withoutOverlapping();
        $schedule->command(CleanServiceBackupFilesCommand::class)->daily();
        $schedule->command(CleanRecycleBinCommand::class)->everySixHours();
        $schedule->command(CleanTempDatabaseExportsCommand::class)->everyThirtyMinutes();
        $schedule->command('arelix:check-nodes')->everyMinute();
        $schedule->command('p:addon:auto-suspend:check')->everyThirtyMinutes();
        $schedule->command('p:server:cleanup-orphaned-subdomains')->daily();
        $schedule->command('p:server:auto-reject-expired-staff-requests')->daily();
        $schedule->command('billing:auto-renew')->daily();
        $schedule->command('billing:cleanup-pending')->daily();
        $schedule->command('arelix:arma-reforger-webhook-log')->everyMinute()->withoutOverlapping();
        $schedule->command('arelix:sync-stats')->everyMinute()->withoutOverlapping();
        $schedule->command('arelix:discord:sync-all')->daily()->withoutOverlapping();
        $schedule->command('arelix:sync-server-splits')->hourly()->withoutOverlapping();
        $schedule->command('p:server:process-wipes')->everyMinute()->withoutOverlapping();
        if (config('backups.prune_age')) {
            $schedule->command(PruneOrphanedBackupsCommand::class)->everyThirtyMinutes();
        }
        if (config('activity.prune_days')) {
            $schedule->command(PruneCommand::class, ['--model' => [ActivityLog::class]])->daily();
        }
        if (config('pterodactyl.telemetry.enabled')) {
            $this->registerTelemetry($schedule);
        }
    }
    private function registerTelemetry(Schedule $schedule): void
    {
        $settingsRepository = app()->make(SettingsRepository::class);
        $uuid = $settingsRepository->get('app:telemetry:uuid');
        if (is_null($uuid)) {
            $uuid = Uuid::uuid4()->toString();
            $settingsRepository->set('app:telemetry:uuid', $uuid);
        }
        $time = hexdec(str_replace('-', '', substr($uuid, 27))) % 1440;
        $hour = floor($time / 60);
        $minute = $time % 60;
        $schedule->call(app()->make(TelemetryCollectionService::class))->description('Collect Telemetry')->dailyAt("$hour:$minute");
    }
}
