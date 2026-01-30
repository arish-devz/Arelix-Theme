<?php

namespace Pterodactyl\Repositories\Eloquent;

use Carbon\Carbon;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Server;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BackupRepository extends EloquentRepository
{
    public function model(): string
    {
        return Backup::class;
    }

    
    public function getBackupsGeneratedDuringTimespan(int $server, int $seconds = 600): array|Collection
    {
        return $this->getBuilder()
            ->withTrashed()
            ->where('server_id', $server)
            ->where(function ($query) {
                $query->whereNull('completed_at')
                    ->orWhere('is_successful', '=', true);
            })
            ->where('created_at', '>=', Carbon::now()->subSeconds($seconds)->toDateTimeString())
            ->get()
            ->toBase();
    }

    
    public function getNonFailedBackups(Server $server): HasMany
    {
        return $server->backups()->where(function ($query) {
            $query->whereNull('completed_at')
                ->orWhere('is_successful', true);
        });
    }
}
