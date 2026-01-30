<?php

namespace Pterodactyl\Services\Helpers;

use Illuminate\Support\Arr;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Contracts\Filesystem\Filesystem;
use Pterodactyl\Exceptions\ManifestDoesNotExistException;

class AssetHashService
{
    
    public const MANIFEST_PATH = './assets/manifest.json';

    private Filesystem $filesystem;

    protected static mixed $manifest = null;

    
    public function __construct(FilesystemManager $filesystem)
    {
        $this->filesystem = $filesystem->createLocalDriver(['root' => public_path()]);
    }

    
    public function url(string $resource): string
    {
        $file = last(explode('/', $resource));
        $data = Arr::get($this->manifest(), $file) ?? $file;

        return str_replace($file, Arr::get($data, 'src') ?? $file, $resource);
    }

    
    public function integrity(string $resource): string
    {
        $file = last(explode('/', $resource));
        $data = array_get($this->manifest(), $file, $file);

        return Arr::get($data, 'integrity') ?? '';
    }

    
    public function css(string $resource): string
    {
        $attributes = [
            'href' => $this->url($resource),
            'rel' => 'stylesheet preload',
            'as' => 'style',
            'crossorigin' => 'anonymous',
            'referrerpolicy' => 'no-referrer',
        ];

        if (config('pterodactyl.assets.use_hash')) {
            $attributes['integrity'] = $this->integrity($resource);
        }

        $output = '<link';
        foreach ($attributes as $key => $value) {
            $output .= " $key=\"$value\"";
        }

        return $output . '>';
    }

    
    public function js(string $resource): string
    {
        $attributes = [
            'src' => $this->url($resource),
            'crossorigin' => 'anonymous',
        ];

        if (config('pterodactyl.assets.use_hash')) {
            $attributes['integrity'] = $this->integrity($resource);
        }

        $output = '<script';
        foreach ($attributes as $key => $value) {
            $output .= " $key=\"$value\"";
        }

        return $output . '></script>';
    }

    
    protected function manifest(): array
    {
        if (static::$manifest === null) {
            self::$manifest = json_decode(
                $this->filesystem->get(self::MANIFEST_PATH),
                true
            );
        }

        $manifest = static::$manifest;
        if ($manifest === null) {
            throw new ManifestDoesNotExistException();
        }

        return $manifest;
    }
}
