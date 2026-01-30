<?php
namespace Pterodactyl\Observers;
use Pterodactyl\Events;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\ServerSplit;
use Pterodactyl\Services\LicenseValidationService;
use Pterodactyl\Services\RolexDev\DiscordBotService;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Support\Facades\Log;
use Exception;
class ServerObserver
{
    use DispatchesJobs;
    public function __construct(
        private LicenseValidationService $licenseValidationService,
        private DiscordBotService $discordBotService
    ) {}
    public function creating(Server $server): void
    {
        event(new Events\Server\Creating($server));
    }
    public function created(Server $server): void
    {
        event(new Events\Server\Created($server));
        $this->discordBotService->syncUserRole($server->user);
    }
    public function deleting(Server $server): void
    {
        $this->handleSubServerDeletion($server);
        event(new Events\Server\Deleting($server));
    }
    public function deleted(Server $server): void
    {
        event(new Events\Server\Deleted($server));
        $this->discordBotService->syncUserRole($server->user);
    }
    public function saving(Server $server): void
    {
        event(new Events\Server\Saving($server));
    }
    public function saved(Server $server): void
    {
        event(new Events\Server\Saved($server));
    }
    public function updating(Server $server): void
    {
        event(new Events\Server\Updating($server));
    }
    public function updated(Server $server): void
    {
        event(new Events\Server\Updated($server));
        $this->discordBotService->syncUserRole($server->user);
    }
    private function handleSubServerDeletion(Server $server): void
    {
        try {
            if (!$this->licenseValidationService->shouldEnableSpecialAddon('server_splitter')) {
                return;
            }
            if (empty($server->masterserver)) {
                return;
            }
            $split = ServerSplit::where('sub_server_id', $server->id)->first();
            if (!$split) {
                Log::warning('Sub-server being deleted but no corresponding ServerSplit record found', [
                    'sub_server_id' => $server->id,
                    'masterserver_uuid' => $server->masterserver,
                ]);
                return;
            }
            $masterServer = $split->masterServer;
            if (!$masterServer) {
                Log::warning('Sub-server being deleted but master server not found', [
                    'sub_server_id' => $server->id,
                    'master_server_id' => $split->master_server_id,
                ]);
                return;
            }
            $updates = [
                'memory' => $masterServer->memory + $split->allocated_memory,
                'cpu' => $masterServer->cpu + $split->allocated_cpu,
                'disk' => $masterServer->disk + $split->allocated_disk,
            ];
            if ($split->allocated_network_allocations > 0) {
                $updates['allocation_limit'] = ($masterServer->allocation_limit ?? 0) + $split->allocated_network_allocations;
            }
            if ($split->allocated_database_limit > 0) {
                $updates['database_limit'] = ($masterServer->database_limit ?? 0) + $split->allocated_database_limit;
            }
            if ($split->allocated_backup_limit > 0) {
                $updates['backup_limit'] = ($masterServer->backup_limit ?? 0) + $split->allocated_backup_limit;
            }
            $masterServer->is_splitting = true;
            $masterServer->update($updates);
            $split->delete();
        } catch (Exception $e) {
            Log::error('Failed to restore resources when deleting sub-server', [
                'sub_server_id' => $server->id,
                'masterserver_uuid' => $server->masterserver,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
