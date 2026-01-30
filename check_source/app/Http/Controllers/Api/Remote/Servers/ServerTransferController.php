<?php

namespace Pterodactyl\Http\Controllers\Api\Remote\Servers;

use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\ServerSubdomain;
use Pterodactyl\Services\SubdomainManager\CloudflareService;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Models\ServerTransfer;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Repositories\Eloquent\ServerRepository;
use Pterodactyl\Repositories\Wings\DaemonServerRepository;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;

class ServerTransferController extends Controller
{
    
    public function __construct(
        private ConnectionInterface $connection,
        private ServerRepository $repository,
        private DaemonServerRepository $daemonServerRepository,
        private CloudflareService $cloudflareService
    ) {
    }

    
    public function failure(string $uuid): JsonResponse
    {
        $server = $this->repository->getByUuid($uuid);
        $transfer = $server->transfer;
        if (is_null($transfer)) {
            throw new ConflictHttpException('Server is not being transferred.');
        }

        return $this->processFailedTransfer($transfer);
    }

    
    public function success(string $uuid): JsonResponse
    {
        $server = $this->repository->getByUuid($uuid);
        $transfer = $server->transfer;
        if (is_null($transfer)) {
            throw new ConflictHttpException('Server is not being transferred.');
        }

        
        $server = $this->connection->transaction(function () use ($server, $transfer) {
            $allocations = array_merge([$transfer->old_allocation], $transfer->old_additional_allocations);

            
            
            Allocation::query()->whereIn('id', $allocations)->update(['server_id' => null]);
            $server->update([
                'allocation_id' => $transfer->new_allocation,
                'node_id' => $transfer->new_node,
            ]);

            $server = $server->fresh();
            $server->transfer->update(['successful' => true]);

            $subdomains = ServerSubdomain::where('server_id', $server->id)->get();
            foreach ($subdomains as $subdomain) {
                if ($subdomain->cloudflare_record && is_array($subdomain->cloudflare_record)) {
                    $cloudflareRecord = $subdomain->cloudflare_record;
                    $zoneId = $cloudflareRecord['zone_id'] ?? null;
                    $recordId = $cloudflareRecord['record_id'] ?? $cloudflareRecord['id'] ?? null;
                    
                    if ($zoneId && $recordId) {
                        try {
                            $this->cloudflareService->deleteDNSRecord($zoneId, $recordId);
                        } catch (\Exception $e) {
                            Log::error('Failed to delete Cloudflare DNS record during server transfer: ' . $e->getMessage());
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
                        } catch (\Exception $e) {
                            Log::error('Failed to delete Cloudflare SRV record during server transfer: ' . $e->getMessage());
                        }
                    }
                }
                
                $subdomain->delete();
            }

            return $server;
        });

        
        
        try {
            $this->daemonServerRepository
                ->setServer($server)
                ->setNode($transfer->oldNode)
                ->delete();
        } catch (DaemonConnectionException $exception) {
            Log::warning($exception, ['transfer_id' => $server->transfer->id]);
        }

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    
    protected function processFailedTransfer(ServerTransfer $transfer): JsonResponse
    {
        $this->connection->transaction(function () use (&$transfer) {
            $transfer->forceFill(['successful' => false])->saveOrFail();

            $allocations = array_merge([$transfer->new_allocation], $transfer->new_additional_allocations);
            Allocation::query()->whereIn('id', $allocations)->update(['server_id' => null]);
        });

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }
}
