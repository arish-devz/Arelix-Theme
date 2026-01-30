<?php

namespace Pterodactyl\Services\Allocations;

use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\ServerSubdomain;
use Pterodactyl\Services\SubdomainManager\CloudflareService;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Contracts\Repository\AllocationRepositoryInterface;
use Pterodactyl\Exceptions\Service\Allocation\ServerUsingAllocationException;

class AllocationDeletionService
{
    
    public function __construct(
        private AllocationRepositoryInterface $repository,
        private CloudflareService $cloudflareService
    ) {
    }

    
    public function handle(Allocation $allocation): int
    {
        if (!is_null($allocation->server_id)) {
            throw new ServerUsingAllocationException(trans('exceptions.allocations.server_using'));
        }

        // Clean up any subdomains associated with this allocation
        $subdomains = ServerSubdomain::where('allocation_id', $allocation->id)->get();
        
        foreach ($subdomains as $subdomain) {
            // Delete Cloudflare A/CNAME record
            if ($subdomain->cloudflare_record && is_array($subdomain->cloudflare_record)) {
                $cloudflareRecord = $subdomain->cloudflare_record;
                $zoneId = $cloudflareRecord['zone_id'] ?? null;
                $recordId = $cloudflareRecord['record_id'] ?? $cloudflareRecord['id'] ?? null;
                
                if ($zoneId && $recordId) {
                    try {
                        $this->cloudflareService->deleteDNSRecord($zoneId, $recordId);
                    } catch (\Exception $e) {
                        Log::error('Failed to delete Cloudflare DNS record during allocation deletion: ' . $e->getMessage());
                    }
                }
            }
            
            // Delete Cloudflare SRV record if exists
            if ($subdomain->srv_record && is_array($subdomain->srv_record)) {
                $srvRecord = $subdomain->srv_record;
                $zoneId = $srvRecord['zone_id'] ?? null;
                $recordId = $srvRecord['record_id'] ?? $srvRecord['id'] ?? null;
                
                if ($zoneId && $recordId) {
                    try {
                        $this->cloudflareService->deleteSRVRecord($zoneId, $recordId);
                    } catch (\Exception $e) {
                        Log::error('Failed to delete Cloudflare SRV record during allocation deletion: ' . $e->getMessage());
                    }
                }
            }
            
            // Delete subdomain database record
            $subdomain->delete();
        }

        return $this->repository->delete($allocation->id);
    }
}
