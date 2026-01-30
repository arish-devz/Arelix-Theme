<?php

namespace Pterodactyl\Services\Backups;

use Ramsey\Uuid\Uuid;
use Carbon\CarbonImmutable;
use Webmozart\Assert\Assert;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Server;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Extensions\Backups\BackupManager;
use Pterodactyl\Repositories\Eloquent\BackupRepository;
use Pterodactyl\Repositories\Wings\DaemonBackupRepository;
use Pterodactyl\Exceptions\Service\Backup\TooManyBackupsException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class InitiateBackupService
{
    private ?array $ignoredFiles;

    private bool $isLocked = false;

    
    public function __construct(
        private BackupRepository $repository,
        private ConnectionInterface $connection,
        private DaemonBackupRepository $daemonBackupRepository,
        private DeleteBackupService $deleteBackupService,
        private BackupManager $backupManager
    ) {
    }

    
    public function setIsLocked(bool $isLocked): self
    {
        $this->isLocked = $isLocked;

        return $this;
    }

    
    public function setIgnoredFiles(?array $ignored): self
    {
        if (is_array($ignored)) {
            foreach ($ignored as $value) {
                Assert::string($value);
            }
        }

        
        
        
        $this->ignoredFiles = is_null($ignored) ? [] : array_filter($ignored, function ($value) {
            return strlen($value) > 0;
        });

        return $this;
    }

    
    public function handle(Server $server, string $name = null, bool $override = false): Backup
    {
        $limit = config('backups.throttles.limit');
        $period = config('backups.throttles.period');
        if ($period > 0) {
            $previous = $this->repository->getBackupsGeneratedDuringTimespan($server->id, $period);
            if ($previous->count() >= $limit) {
                $message = sprintf('Only %d backups may be generated within a %d second span of time.', $limit, $period);

                throw new TooManyRequestsHttpException(CarbonImmutable::now()->diffInSeconds($previous->last()->created_at->addSeconds($period)), $message);
            }
        }

        
        
        $successful = $this->repository->getNonFailedBackups($server);
        if (!$server->backup_limit || $successful->count() >= $server->backup_limit) {
            
            if (!$override || $server->backup_limit <= 0) {
                throw new TooManyBackupsException($server->backup_limit);
            }

            
            
            
            
            $oldest = $successful->where('is_locked', false)->orderBy('created_at')->first();
            if (!$oldest) {
                throw new TooManyBackupsException($server->backup_limit);
            }

            $this->deleteBackupService->handle($oldest);
        }

        return $this->connection->transaction(function () use ($server, $name) {
            
            $backup = $this->repository->create([
                'server_id' => $server->id,
                'uuid' => Uuid::uuid4()->toString(),
                'name' => trim($name) ?: sprintf('Backup at %s', CarbonImmutable::now()->toDateTimeString()),
                'ignored_files' => array_values($this->ignoredFiles ?? []),
                'disk' => $this->backupManager->getDefaultAdapter(),
                'is_locked' => $this->isLocked,
            ], true, true);

            $this->daemonBackupRepository->setServer($server)
                ->setBackupAdapter($this->backupManager->getDefaultAdapter())
                ->backup($backup);

            return $backup;
        });
    }
}
