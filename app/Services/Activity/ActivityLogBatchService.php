<?php

namespace Pterodactyl\Services\Activity;

use Ramsey\Uuid\Uuid;

class ActivityLogBatchService
{
    protected int $transaction = 0;
    protected ?string $uuid = null;

    
    public function uuid(): ?string
    {
        return $this->uuid;
    }

    
    public function start(): void
    {
        if ($this->transaction === 0) {
            $this->uuid = Uuid::uuid4()->toString();
        }

        ++$this->transaction;
    }

    
    public function end(): void
    {
        $this->transaction = max(0, $this->transaction - 1);

        if ($this->transaction === 0) {
            $this->uuid = null;
        }
    }

    
    public function transaction(\Closure $callback): mixed
    {
        $this->start();
        $result = $callback($this->uuid());
        $this->end();

        return $result;
    }
}
