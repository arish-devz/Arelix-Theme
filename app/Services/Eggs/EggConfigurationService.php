<?php

namespace Pterodactyl\Services\Eggs;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Pterodactyl\Models\Server;
use Pterodactyl\Services\Servers\ServerConfigurationStructureService;

class EggConfigurationService
{
    
    public function __construct(private ServerConfigurationStructureService $configurationStructureService)
    {
    }

    
    public function handle(Server $server): array
    {
        $configs = $this->replacePlaceholders(
            $server,
            json_decode($server->egg->inherit_config_files)
        );

        return [
            'startup' => $this->convertStartupToNewFormat(json_decode($server->egg->inherit_config_startup, true)),
            'stop' => $this->convertStopToNewFormat($server->egg->inherit_config_stop),
            'configs' => $configs,
        ];
    }

    
    protected function convertStartupToNewFormat(array $startup): array
    {
        $done = Arr::get($startup, 'done');

        return [
            'done' => is_string($done) ? [$done] : $done,
            'user_interaction' => [],
            'strip_ansi' => Arr::get($startup, 'strip_ansi') ?? false,
        ];
    }

    
    protected function convertStopToNewFormat(string $stop): array
    {
        if (!Str::startsWith($stop, '^')) {
            return [
                'type' => 'command',
                'value' => $stop,
            ];
        }

        $signal = substr($stop, 1);
        if (strtoupper($signal) === 'C') {
            return [
                'type' => 'stop',
                'value' => null,
            ];
        }

        return [
            'type' => 'signal',
            'value' => strtoupper($signal),
        ];
    }

    protected function replacePlaceholders(Server $server, object $configs): array
    {
        $structure = $this->configurationStructureService->handle($server, [], true);

        $response = [];
        foreach ($configs as $file => $data) {
            if (!is_object($data) || !isset($data->find)) {
                continue;
            }

            $append = array_merge((array) $data, ['file' => $file, 'replace' => []]);

            foreach ($this->iterate($data->find, $structure) as $find => $replace) {
                if (is_object($replace)) {
                    foreach ($replace as $match => $replaceWith) {
                        $append['replace'][] = [
                            'match' => $find,
                            'if_value' => $match,
                            'replace_with' => $replaceWith,
                        ];
                    }

                    continue;
                }

                $append['replace'][] = [
                    'match' => $find,
                    'replace_with' => $replace,
                ];
            }

            unset($append['find']);

            $response[] = $append;
        }

        return $response;
    }

    
    protected function replaceLegacyModifiers(string $key, string $value): string
    {
        switch ($key) {
            case 'config.docker.interface':
                $replace = 'config.docker.network.interface';
                break;
            case 'server.build.env.SERVER_MEMORY':
            case 'env.SERVER_MEMORY':
                $replace = 'server.build.memory';
                break;
            case 'server.build.env.SERVER_IP':
            case 'env.SERVER_IP':
                $replace = 'server.build.default.ip';
                break;
            case 'server.build.env.SERVER_PORT':
            case 'env.SERVER_PORT':
                $replace = 'server.build.default.port';
                break;
            default:
                
                $replace = $key;
        }

        return str_replace("{{{$key}}}", "{{{$replace}}}", $value);
    }

    protected function matchAndReplaceKeys(mixed $value, array $structure): mixed
    {
        preg_match_all('/{{(?<key>[\w.-]*)}}/', $value, $matches);

        foreach ($matches['key'] as $key) {
            if (!Str::startsWith($key, ['server.', 'env.', 'config.'])) {
                continue;
            }

            if (!is_string($value)) {
                continue;
            }

            $value = $this->replaceLegacyModifiers($key, $value);

            if (Str::startsWith($key, 'config.')) {
                continue;
            }

            if (Str::startsWith($key, 'server.')) {
                $plucked = Arr::get($structure, preg_replace('/^server\./', '', $key), '');

                $value = str_replace("{{{$key}}}", $plucked, $value);
                continue;
            }

            $plucked = Arr::get(
                $structure,
                preg_replace('/^env\./', 'build.env.', $key),
                ''
            );

            $value = str_replace("{{{$key}}}", $plucked, $value);
        }

        return $value;
    }

    private function iterate(mixed $data, array $structure): mixed
    {
        if (!is_iterable($data) && !is_object($data)) {
            return $data;
        }

        if (is_array($data)) {
            $clone = $data;
        } else {
            $clone = clone $data;
        }
        foreach ($clone as $key => &$value) {
            if (is_iterable($value) || is_object($value)) {
                $value = $this->iterate($value, $structure);

                continue;
            }

            $value = $this->matchAndReplaceKeys($value, $structure);
        }

        return $clone;
    }
}
