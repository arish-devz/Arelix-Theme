<?php

namespace Pterodactyl\Contracts\Repository;

use Pterodactyl\Models\Node;
use Illuminate\Support\Collection;

interface NodeRepositoryInterface extends RepositoryInterface
{
    public const THRESHOLD_PERCENTAGE_LOW = 75;
    public const THRESHOLD_PERCENTAGE_MEDIUM = 90;

    
    public function getUsageStats(Node $node): array;

    
    public function getUsageStatsRaw(Node $node): array;

    
    public function loadLocationAndServerCount(Node $node, bool $refresh = false): Node;

    
    public function loadNodeAllocations(Node $node, bool $refresh = false): Node;

    
    public function getNodesForServerCreation(): Collection;
}
