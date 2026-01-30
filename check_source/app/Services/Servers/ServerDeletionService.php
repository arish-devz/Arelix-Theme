<?php
namespace Pterodactyl\Services\Servers;
use Illuminate\Http\Response;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\ServerSubdomain;
use Pterodactyl\Services\SubdomainManager\CloudflareService;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Repositories\Wings\DaemonServerRepository;
use Pterodactyl\Services\Databases\DatabaseManagementService;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Exception;
class ServerDeletionService
{
    protected bool $force = false;
    public function __construct(
        private ConnectionInterface $connection,
        private DaemonServerRepository $daemonServerRepository,
        private DatabaseManagementService $databaseManagementService,
        private CloudflareService $cloudflareService
    ) {
    }
    public function withForce(bool $bool = true): self
    {
        $this->force = $bool;
        return $this;
    }
    public function handle(Server $server): void
    {
        try {
            $this->daemonServerRepository->setServer($server)->delete();
        } catch (DaemonConnectionException $exception) {
            if (!$this->force && $exception->getStatusCode() !== Response::HTTP_NOT_FOUND) {
                throw $exception;
            }
            Log::warning($exception);
        }
        $this->connection->transaction(function () use ($server) {
            $subdomains = ServerSubdomain::where('server_id', $server->id)->get();
            foreach ($subdomains as $subdomain) {
                if ($subdomain->cloudflare_record && is_array($subdomain->cloudflare_record)) {
                    $cloudflareRecord = $subdomain->cloudflare_record;
                    $zoneId = $cloudflareRecord['zone_id'] ?? null;
                    $recordId = $cloudflareRecord['record_id'] ?? $cloudflareRecord['id'] ?? null;
                    if ($zoneId && $recordId) {
                        try {
                            $this->cloudflareService->deleteDNSRecord($zoneId, $recordId);
                        } catch (Exception $e) {
                            Log::error('Failed to delete Cloudflare A DNS record during server deletion: ' . $e->getMessage());
                        }
                    }
                }
                if ($subdomain->srv_record && is_array($subdomain->srv_record)) {
                    $srvRecord = $subdomain->srv_record;
                    $zoneId = $srvRecord['zone_id'] ?? null;
                    $recordId = $srvRecord['record_id'] ?? $srvRecord['id'] ?? null;
                    if ($zoneId && $recordId) {
                        try {
                            $this->cloudflareService->deleteSRVRecord($zoneId, $recordId);
                        } catch (Exception $e) {
                            Log::error('Failed to delete Cloudflare SRV DNS record during server deletion: ' . $e->getMessage());
                        }
                    }
                }
            }
            ServerSubdomain::where('server_id', $server->id)->delete();
            foreach ($server->databases as $database) {
                try {
                    $this->databaseManagementService->delete($database);
                } catch (Exception $exception) {
                    if (!$this->force) {
                        throw $exception;
                    }
                    $database->delete();
                    Log::warning($exception);
                }
            }
            $server->delete();
        });
    }
}
