<?php

namespace Pterodactyl\Extensions\Backups;

use Closure;
use Aws\S3\S3Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Webmozart\Assert\Assert;
use Illuminate\Foundation\Application;
use League\Flysystem\FilesystemAdapter;
use Pterodactyl\Extensions\Filesystem\S3Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class BackupManager
{
    protected ConfigRepository $config;

    
    protected array $adapters = [];

    
    protected array $customCreators;

    
    public function __construct(protected Application $app)
    {
        $this->config = $app->make(ConfigRepository::class);
    }

    
    public function adapter(string $name = null): FilesystemAdapter
    {
        return $this->get($name ?: $this->getDefaultAdapter());
    }

    
    public function set(string $name, FilesystemAdapter $disk): self
    {
        $this->adapters[$name] = $disk;

        return $this;
    }

    
    protected function get(string $name): FilesystemAdapter
    {
        return $this->adapters[$name] = $this->resolve($name);
    }

    
    protected function resolve(string $name): FilesystemAdapter
    {
        $config = $this->getConfig($name);

        if (empty($config['adapter'])) {
            throw new \InvalidArgumentException("Backup disk [$name] does not have a configured adapter.");
        }

        $adapter = $config['adapter'];

        if (isset($this->customCreators[$name])) {
            return $this->callCustomCreator($config);
        }

        $adapterMethod = 'create' . Str::studly($adapter) . 'Adapter';
        if (method_exists($this, $adapterMethod)) {
            $instance = $this->{$adapterMethod}($config);

            Assert::isInstanceOf($instance, FilesystemAdapter::class);

            return $instance;
        }

        throw new \InvalidArgumentException("Adapter [$adapter] is not supported.");
    }

    
    protected function callCustomCreator(array $config): mixed
    {
        return $this->customCreators[$config['adapter']]($this->app, $config);
    }

    
    public function createWingsAdapter(array $config): FilesystemAdapter
    {
        return new InMemoryFilesystemAdapter();
    }

    
    public function createS3Adapter(array $config): FilesystemAdapter
    {
        $config['version'] = 'latest';

        if (!empty($config['key']) && !empty($config['secret'])) {
            $config['credentials'] = Arr::only($config, ['key', 'secret', 'token']);
        }

        $client = new S3Client($config);

        return new S3Filesystem($client, $config['bucket'], $config['prefix'] ?? '', $config['options'] ?? []);
    }

    
    protected function getConfig(string $name): array
    {
        return $this->config->get("backups.disks.$name") ?: [];
    }

    
    public function getDefaultAdapter(): string
    {
        return $this->config->get('backups.default');
    }

    
    public function setDefaultAdapter(string $name): void
    {
        $this->config->set('backups.default', $name);
    }

    
    public function forget(array|string $adapter): self
    {
        foreach ((array) $adapter as $adapterName) {
            unset($this->adapters[$adapterName]);
        }

        return $this;
    }

    
    public function extend(string $adapter, \Closure $callback): self
    {
        $this->customCreators[$adapter] = $callback;

        return $this;
    }
}
