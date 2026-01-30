<?php

namespace Pterodactyl\Contracts\Repository;

use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface DatabaseRepositoryInterface extends RepositoryInterface
{
    public const DEFAULT_CONNECTION_NAME = 'dynamic';

    
    public function setConnection(string $connection): self;

    
    public function getConnection(): string;

    
    public function getDatabasesForServer(int $server): Collection;

    
    public function getDatabasesForHost(int $host, int $count = 25): LengthAwarePaginator;

    
    public function createDatabase(string $database): bool;

    
    public function createUser(string $username, string $remote, string $password, ?int $max_connections): bool;

    
    public function assignUserToDatabase(string $database, string $username, string $remote): bool;

    
    public function flush(): bool;

    
    public function dropDatabase(string $database): bool;

    
    public function dropUser(string $username, string $remote): bool;
}
