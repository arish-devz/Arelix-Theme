<?php
namespace Pterodactyl\Repositories\Wings;
use Illuminate\Support\Arr;
use Webmozart\Assert\Assert;
use Pterodactyl\Models\Server;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\TransferException;
use Pterodactyl\Exceptions\Http\Server\FileSizeTooLargeException;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
class DaemonFileRepository extends DaemonRepository
{
    public function getContent(string $path, int $notLargerThan = null): string
    {
        Assert::isInstanceOf($this->server, Server::class);
        try {
            $response = $this->getHttpClient()->get(
                sprintf('/api/servers/%s/files/contents', $this->server->uuid),
                [
                    'query' => ['file' => $path],
                ]
            );
        } catch (ClientException|TransferException $exception) {
            throw new DaemonConnectionException($exception);
        }
        $length = (int) Arr::get($response->getHeader('Content-Length'), 0, 0);
        if ($notLargerThan && $length > $notLargerThan) {
            throw new FileSizeTooLargeException();
        }
        return $response->getBody()->__toString();
    }
    public function putContent(string $path, $content, ?int $timeout = null): ResponseInterface
    {
        Assert::isInstanceOf($this->server, Server::class);
        try {
            $options = [
                'query' => ['file' => $path],
                'body' => $content,
            ];
            if ($timeout !== null) {
                $options['timeout'] = $timeout;
            }
            return $this->getHttpClient()->post(
                sprintf('/api/servers/%s/files/write', $this->server->uuid),
                $options
            );
        } catch (TransferException $exception) {
            throw new DaemonConnectionException($exception);
        }
    }
    public function getDirectory(string $path): array
    {
        Assert::isInstanceOf($this->server, Server::class);
        try {
            $response = $this->getHttpClient()->get(
                sprintf('/api/servers/%s/files/list-directory', $this->server->uuid),
                [
                    'query' => ['directory' => $path],
                ]
            );
        } catch (TransferException $exception) {
            throw new DaemonConnectionException($exception);
        }
        return json_decode($response->getBody(), true);
    }
    public function createDirectory(string $name, string $path): ResponseInterface
    {
        Assert::isInstanceOf($this->server, Server::class);
        try {
            return $this->getHttpClient()->post(
                sprintf('/api/servers/%s/files/create-directory', $this->server->uuid),
                [
                    'json' => [
                        'name' => $name,
                        'path' => $path,
                    ],
                ]
            );
        } catch (TransferException $exception) {
            throw new DaemonConnectionException($exception);
        }
    }
    public function renameFiles(?string $root, array $files): ResponseInterface
    {
        Assert::isInstanceOf($this->server, Server::class);
        try {
            return $this->getHttpClient()->put(
                sprintf('/api/servers/%s/files/rename', $this->server->uuid),
                [
                    'json' => [
                        'root' => $root ?? '/',
                        'files' => $files,
                    ],
                ]
            );
        } catch (TransferException $exception) {
            throw new DaemonConnectionException($exception);
        }
    }
    public function copyFile(string $location): ResponseInterface
    {
        Assert::isInstanceOf($this->server, Server::class);
        try {
            return $this->getHttpClient()->post(
                sprintf('/api/servers/%s/files/copy', $this->server->uuid),
                [
                    'json' => [
                        'location' => $location,
                    ],
                ]
            );
        } catch (TransferException $exception) {
            throw new DaemonConnectionException($exception);
        }
    }
    public function deleteFiles(?string $root, array $files): ResponseInterface
    {
        Assert::isInstanceOf($this->server, Server::class);
        try {
            return $this->getHttpClient()->post(
                sprintf('/api/servers/%s/files/delete', $this->server->uuid),
                [
                    'json' => [
                        'root' => $root ?? '/',
                        'files' => $files,
                    ],
                ]
            );
        } catch (TransferException $exception) {
            throw new DaemonConnectionException($exception);
        }
    }
    public function compressFiles(?string $root, array $files): array
    {
        Assert::isInstanceOf($this->server, Server::class);
        try {
            $response = $this->getHttpClient()->post(
                sprintf('/api/servers/%s/files/compress', $this->server->uuid),
                [
                    'json' => [
                        'root' => $root ?? '/',
                        'files' => $files,
                    ],
                    'timeout' => 60 * 15,
                ]
            );
        } catch (TransferException $exception) {
            throw new DaemonConnectionException($exception);
        }
        return json_decode($response->getBody(), true);
    }
    public function decompressFile(?string $root, string $file): ResponseInterface
    {
        Assert::isInstanceOf($this->server, Server::class);
        try {
            return $this->getHttpClient()->post(
                sprintf('/api/servers/%s/files/decompress', $this->server->uuid),
                [
                    'json' => [
                        'root' => $root ?? '/',
                        'file' => $file,
                    ],
                    'timeout' => 60 * 15,
                ]
            );
        } catch (TransferException $exception) {
            throw new DaemonConnectionException($exception);
        }
    }
    public function chmodFiles(?string $root, array $files): ResponseInterface
    {
        Assert::isInstanceOf($this->server, Server::class);
        try {
            return $this->getHttpClient()->post(
                sprintf('/api/servers/%s/files/chmod', $this->server->uuid),
                [
                    'json' => [
                        'root' => $root ?? '/',
                        'files' => $files,
                    ],
                ]
            );
        } catch (TransferException $exception) {
            throw new DaemonConnectionException($exception);
        }
    }
    public function pull(string $url, ?string $directory, array $params = []): ResponseInterface
    {
        Assert::isInstanceOf($this->server, Server::class);
        $attributes = [
            'url' => $url,
            'root' => $directory ?? '/',
            'file_name' => $params['filename'] ?? null,
            'use_header' => $params['use_header'] ?? null,
            'foreground' => $params['foreground'] ?? null,
        ];
        $timeout = $params['timeout'] ?? null;
        try {
            $options = [
                'json' => array_filter($attributes, fn ($value) => !is_null($value)),
            ];
            if ($timeout) {
                $options['timeout'] = $timeout;
            }
            return $this->getHttpClient()->post(
                sprintf('/api/servers/%s/files/pull', $this->server->uuid),
                $options
            );
        } catch (TransferException $exception) {
            throw new DaemonConnectionException($exception);
        }
    }
}
