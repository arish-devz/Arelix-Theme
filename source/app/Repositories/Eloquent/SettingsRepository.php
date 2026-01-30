<?php

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\Setting;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class SettingsRepository extends EloquentRepository implements SettingsRepositoryInterface
{
    private static array $cache = [];

    private static array $databaseMiss = [];

    
    public function model(): string
    {
        return Setting::class;
    }

    
    public function set(string $key, string $value = null)
    {
        
        $this->clearCache($key);
        $this->withoutFreshModel()->updateOrCreate(['key' => $key], ['value' => $value ?? '']);

        self::$cache[$key] = $value;
    }

    
    public function get(string $key, mixed $default = null): mixed
    {
        
        
        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        } elseif (array_key_exists($key, self::$databaseMiss)) {
            return value($default);
        }

        $instance = $this->getBuilder()->where('key', $key)->first();
        if (is_null($instance)) {
            self::$databaseMiss[$key] = true;

            return value($default);
        }

        return self::$cache[$key] = $instance->value;
    }

    
    public function forget(string $key)
    {
        $this->clearCache($key);
        $this->deleteWhere(['key' => $key]);
    }

    
    private function clearCache(string $key)
    {
        unset(self::$cache[$key], self::$databaseMiss[$key]);
    }
}
