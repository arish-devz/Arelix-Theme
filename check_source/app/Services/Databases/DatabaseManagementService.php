<?php
namespace Pterodactyl\Services\Databases;
use Exception;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Database;
use Pterodactyl\Helpers\Utilities;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Contracts\Encryption\Encrypter;
use Pterodactyl\Extensions\DynamicDatabaseConnection;
use Pterodactyl\Repositories\Eloquent\DatabaseRepository;
use Pterodactyl\Exceptions\Repository\DuplicateDatabaseNameException;
use Pterodactyl\Exceptions\Service\Database\TooManyDatabasesException;
use Pterodactyl\Exceptions\Service\Database\DatabaseClientFeatureNotEnabledException;
class DatabaseManagementService
{
    private const MATCH_NAME_REGEX = '/^(s[\d]+_)(.*)$/';
    protected bool $validateDatabaseLimit = true;
    public function __construct(
        protected ConnectionInterface $connection,
        protected DynamicDatabaseConnection $dynamic,
        protected Encrypter $encrypter,
        protected DatabaseRepository $repository
    ) {
    }
    public static function generateUniqueDatabaseName(string $name, int $serverId): string
    {
        return sprintf('s%d_%s', $serverId, substr($name, 0, 48 - strlen("s{$serverId}_")));
    }
    public function setValidateDatabaseLimit(bool $validate): self
    {
        $this->validateDatabaseLimit = $validate;
        return $this;
    }
    public function create(Server $server, array $data): Database
    {
        if (!config('pterodactyl.client_features.databases.enabled')) {
            throw new DatabaseClientFeatureNotEnabledException();
        }
        if ($this->validateDatabaseLimit) {
            if (!is_null($server->database_limit) && $server->databases()->count() >= $server->database_limit) {
                throw new TooManyDatabasesException();
            }
        }
        if (empty($data['database']) || !preg_match(self::MATCH_NAME_REGEX, $data['database'])) {
            throw new \InvalidArgumentException('The database name passed to DatabaseManagementService::handle MUST be prefixed with "s{server_id}_".');
        }
        $data = array_merge($data, [
            'server_id' => $server->id,
            'username' => sprintf('u%d_%s', $server->id, str_random(10)),
            'password' => $this->encrypter->encrypt(
                Utilities::randomStringWithSpecialCharacters(24)
            ),
        ]);
        $database = null;
        try {
            return $this->connection->transaction(function () use ($data, &$database) {
                $database = $this->createModel($data);
                $this->dynamic->set('dynamic', $data['database_host_id']);
                $this->repository->createDatabase($database->database);
                $this->repository->createUser(
                    $database->username,
                    $database->remote,
                    $this->encrypter->decrypt($database->password),
                    $database->max_connections
                );
                $this->repository->assignUserToDatabase($database->database, $database->username, $database->remote);
                $this->repository->flush();
                return $database;
            });
        } catch (Exception $exception) {
            try {
                if ($database instanceof Database) {
                    $this->repository->dropDatabase($database->database);
                    $this->repository->dropUser($database->username, $database->remote);
                    $this->repository->flush();
                }
            } catch (Exception $deletionException) {
            }
            throw $exception;
        }
    }
    public function delete(Database $database): ?bool
    {
        $this->dynamic->set('dynamic', $database->database_host_id);
        $this->repository->dropDatabase($database->database);
        $this->repository->dropUser($database->username, $database->remote);
        $this->repository->flush();
        return $database->delete();
    }
    protected function createModel(array $data): Database
    {
        $exists = Database::query()->where('server_id', $data['server_id'])
            ->where('database', $data['database'])
            ->exists();
        if ($exists) {
            throw new DuplicateDatabaseNameException('A database with that name already exists for this server.');
        }
        $database = (new Database())->forceFill($data);
        $database->saveOrFail();
        return $database;
    }
    public function clear(Database $database): void
    {
        $this->dynamic->set('dynamic', $database->database_host_id);
        $listCommand = sprintf(
            'mysql -h %s -P %s -u %s -p%s %s -e "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = \'%s\';"',
            escapeshellarg($database->host->host),
            escapeshellarg($database->host->port),
            escapeshellarg($database->username),
            escapeshellarg($this->encrypter->decrypt($database->password)),
            escapeshellarg($database->database),
            $database->database
        );
        exec($listCommand, $tables, $listReturnCode);
        if ($listReturnCode === 0 && !empty($tables)) {
            array_shift($tables);
            if (!empty($tables)) {
                $escapedTables = array_map(function($table) {
                    return '`' . str_replace('`', '``', $table) . '`';
                }, $tables);
                $dropCommand = sprintf(
                    "mysql -h %s -P %s -u %s -p%s %s -e %s",
                    escapeshellarg($database->host->host),
                    escapeshellarg($database->host->port),
                    escapeshellarg($database->username),
                    escapeshellarg($this->encrypter->decrypt($database->password)),
                    escapeshellarg($database->database),
                    escapeshellarg("SET FOREIGN_KEY_CHECKS = 0; DROP TABLE IF EXISTS " . implode(', ', $escapedTables) . "; SET FOREIGN_KEY_CHECKS = 1;")
                );
                exec($dropCommand, $dropOutput, $dropReturnCode);
                if ($dropReturnCode !== 0) {
                    \Log::error('Database clear drop command failed', [
                        'database_id' => $database->id,
                        'database_name' => $database->database,
                        'tables' => $tables,
                        'escaped_tables' => $escapedTables,
                        'command' => $dropCommand,
                        'return_code' => $dropReturnCode,
                        'output' => $dropOutput
                    ]);
                    throw new Exception('Failed to drop tables: ' . implode(' ', $dropOutput));
                }
            }
        } elseif ($listReturnCode !== 0) {
            \Log::error('Database clear list command failed', [
                'database_id' => $database->id,
                'database_name' => $database->database,
                'return_code' => $listReturnCode,
                'output' => $tables
            ]);
            throw new Exception('Failed to list tables: ' . implode(' ', $tables));
        }
        $this->repository->flush();
    }
    public function importFromFile(Database $database, \Illuminate\Http\UploadedFile $file, bool $deleteAllData = false): void
    {
        $this->dynamic->set('dynamic', $database->database_host_id);
        if ($deleteAllData) {
            $this->clear($database);
        }
        $command = sprintf(
            'mysql -h %s -P %s -u %s -p%s %s < %s',
            escapeshellarg($database->host->host),
            escapeshellarg($database->host->port),
            escapeshellarg($database->username),
            escapeshellarg($this->encrypter->decrypt($database->password)),
            escapeshellarg($database->database),
            escapeshellarg($file->getRealPath())
        );
        exec($command, $output, $returnCode);
        if ($returnCode !== 0) {
            throw new Exception('Failed to import database from file: ' . implode(' ', $output));
        }
        $this->repository->flush();
    }
    public function importFromRemote(Database $database, array $remoteConfig, bool $deleteAllData = false): void
    {
        $this->dynamic->set('dynamic', $database->database_host_id);
        if ($deleteAllData) {
            $this->clear($database);
        }
        $remoteCommand = sprintf(
            'mysqldump -h %s -P %s -u %s -p%s %s',
            escapeshellarg($remoteConfig['host']),
            escapeshellarg($remoteConfig['port']),
            escapeshellarg($remoteConfig['username']),
            escapeshellarg($remoteConfig['password']),
            escapeshellarg($remoteConfig['database'])
        );
        $localCommand = sprintf(
            'mysql -h %s -P %s -u %s -p%s %s',
            escapeshellarg($database->host->host),
            escapeshellarg($database->host->port),
            escapeshellarg($database->username),
            escapeshellarg($this->encrypter->decrypt($database->password)),
            escapeshellarg($database->database)
        );
        $fullCommand = "{$remoteCommand} | {$localCommand}";
        exec($fullCommand, $output, $returnCode);
        if ($returnCode !== 0) {
            throw new Exception('Failed to import from remote database');
        }
        $this->repository->flush();
    }
    public function export(Database $database): string
    {
        $this->dynamic->set('dynamic', $database->database_host_id);
        $command = sprintf(
            'mysqldump -h %s -P %s -u %s -p%s %s',
            escapeshellarg($database->host->host),
            escapeshellarg($database->host->port),
            escapeshellarg($database->username),
            escapeshellarg($this->encrypter->decrypt($database->password)),
            escapeshellarg($database->database)
        );
        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);
        if ($returnCode !== 0) {
            \Log::error('Database export failed', [
                'database_id' => $database->id,
                'database_name' => $database->database,
                'host' => $database->host->host,
                'return_code' => $returnCode,
                'command_output' => $output
            ]);
            throw new Exception('Failed to export database: mysqldump returned error code ' . $returnCode);
        }
        $sqlContent = implode("\n", $output);
        if (empty($sqlContent)) {
            \Log::warning('Database export produced empty content', [
                'database_id' => $database->id,
                'database_name' => $database->database
            ]);
        }
        return $sqlContent;
    }
}
