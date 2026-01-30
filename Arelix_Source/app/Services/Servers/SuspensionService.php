<?php
namespace Pterodactyl\Services\Servers;
use Webmozart\Assert\Assert;
use Pterodactyl\Models\Server;
use Pterodactyl\Repositories\Wings\DaemonServerRepository;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Pterodactyl\Services\LicenseValidationService;
use Exception;
class SuspensionService
{
    public const ACTION_SUSPEND = 'suspend';
    public const ACTION_UNSUSPEND = 'unsuspend';
    public function __construct(
        private DaemonServerRepository $daemonServerRepository,
        private LicenseValidationService $licenseValidationService
    ) {
    }
    public function toggle(Server $server, string $action = self::ACTION_SUSPEND): void
    {
        Assert::oneOf($action, [self::ACTION_SUSPEND, self::ACTION_UNSUSPEND]);
        $isSuspending = $action === self::ACTION_SUSPEND;
        if ($isSuspending === $server->isSuspended()) {
            return;
        }
        if (!is_null($server->transfer)) {
            throw new ConflictHttpException('Cannot toggle suspension status on a server that is currently being transferred.');
        }
        $server->update([
            'status' => $isSuspending ? Server::STATUS_SUSPENDED : null,
        ]);
        try {
            $this->daemonServerRepository->setServer($server)->sync();
            $this->handleSubServerSuspension($server, $isSuspending);
        } catch (Exception $exception) {
            $server->update([
                'status' => $isSuspending ? null : Server::STATUS_SUSPENDED,
            ]);
            throw $exception;
        }
    }
    private function handleSubServerSuspension(Server $server, bool $isSuspending): void
    {
        try {
            if (!$this->licenseValidationService->shouldEnableSpecialAddon('server_splitter')) {
                return;
            }
            $subServers = $server->splits()->with('subServer')->get()->pluck('subServer')->filter();
            if ($subServers->isEmpty()) {
                return;
            }
            foreach ($subServers as $subServer) {
                try {
                    $subServer->update([
                        'status' => $isSuspending ? Server::STATUS_SUSPENDED : null,
                    ]);
                    $this->daemonServerRepository->setServer($subServer)->sync();
                } catch (Exception $e) {
                    \Log::warning('Failed to ' . ($isSuspending ? 'suspend' : 'unsuspend') . ' sub-server', [
                        'master_server_id' => $server->id,
                        'sub_server_id' => $subServer->id,
                        'action' => $isSuspending ? 'suspend' : 'unsuspend',
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } catch (Exception $e) {
            \Log::warning('Failed to handle sub-server suspension for master server', [
                'master_server_id' => $server->id,
                'action' => $isSuspending ? 'suspend' : 'unsuspend',
                'error' => $e->getMessage(),
            ]);
        }
    }
}
