<?php

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\Database;
use Illuminate\Support\Collection;
use Illuminate\Foundation\Application;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface;

class DatabaseRepository extends EloquentRepository implements DatabaseRepositoryInterface
{
    protected string $connection = self::DEFAULT_CONNECTION_NAME;

    
    public function __construct(Application $application, private DatabaseManager $database)
    {
        parent::__construct($application);
    }

    
    public function model(): string
    {
        return Database::class;
    }

    
    public function getConnection(): string
    {
        return $this->connection;
    }

    
    public function setConnection(string $connection): self
    {
        $this->connection = $connection;

        return $this;
    }

    
    public function getDatabasesForServer(int $server): Collection
    {
        return $this->getBuilder()->with('host')->where('server_id', $server)->get($this->getColumns());
    }

    
    public function getDatabasesForHost(int $host, int $count = 25): LengthAwarePaginator
    {
        return $this->getBuilder()->with('server')
            ->where('database_host_id', $host)
            ->paginate($count, $this->getColumns());
    }

    
    public function createDatabase(string $database): bool
    {
        return $this->run(sprintf('CREATE DATABASE IF NOT EXISTS `%s`', $database));
    }

    
    public function createUser(string $username, string $remote, string $password, ?int $max_connections): bool
    {
        $args = [$username, $remote, $password];
        $command = 'CREATE USER `%s`@`%s` IDENTIFIED BY \'%s\'';

        if (!empty($max_connections)) {
            $args[] = $max_connections;
            $command .= ' WITH MAX_USER_CONNECTIONS %s';
        }

        return $this->run(sprintf($command, ...$args));
    }

    
    public function assignUserToDatabase(string $database, string $username, string $remote): bool
    {
        return $this->run(sprintf(
            'GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, ALTER, REFERENCES, INDEX, LOCK TABLES, CREATE ROUTINE, ALTER ROUTINE, EXECUTE, CREATE TEMPORARY TABLES, CREATE VIEW, SHOW VIEW, EVENT, TRIGGER ON `%s`.* TO `%s`@`%s`',
            $database,
            $username,
            $remote
        ));
    }

    
    public function flush(): bool
    {
        return $this->run('FLUSH PRIVILEGES');
    }

    
    public function dropDatabase(string $database): bool
    {
        return $this->run(sprintf('DROP DATABASE IF EXISTS `%s`', $database));
    }

    
    public function dropUser(string $username, string $remote): bool
    {
        return $this->run(sprintf('DROP USER IF EXISTS `%s`@`%s`', $username, $remote));
    }

    
    private function run(string $statement): bool
    {
        return $this->database->connection($this->getConnection())->statement($statement);
    }
}
