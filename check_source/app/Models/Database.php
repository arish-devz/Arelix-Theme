<?php

namespace Pterodactyl\Models;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Pterodactyl\Contracts\Extensions\HashidsInterface;


class Database extends Model
{
    
    public const RESOURCE_NAME = 'server_database';

    
    protected $table = 'databases';

    
    protected $hidden = ['password'];

    
    protected $fillable = [
        'server_id', 'database_host_id', 'database', 'username', 'password', 'remote', 'max_connections',
    ];

    
    protected $casts = [
        'server_id' => 'integer',
        'database_host_id' => 'integer',
        'max_connections' => 'integer',
    ];

    public static array $validationRules = [
        'server_id' => 'required|numeric|exists:servers,id',
        'database_host_id' => 'required|exists:database_hosts,id',
        'database' => 'required|string|alpha_dash|between:3,48',
        'username' => 'string|alpha_dash|between:3,100',
        'max_connections' => 'nullable|integer',
        'remote' => 'required|string|regex:/^[\w\-\/.%:]+$/',
        'password' => 'string',
    ];

    
    public function getRouteKeyName(): string
    {
        return $this->getKeyName();
    }

    
    public function resolveRouteBinding($value, $field = null): ?\Illuminate\Database\Eloquent\Model
    {
        if (is_scalar($value) && ($field ?? $this->getRouteKeyName()) === 'id') {
            $value = ctype_digit((string) $value)
                ? $value
                : Container::getInstance()->make(HashidsInterface::class)->decodeFirst($value);
        }

        return $this->where($field ?? $this->getRouteKeyName(), $value)->firstOrFail();
    }

    
    public function host(): BelongsTo
    {
        return $this->belongsTo(DatabaseHost::class, 'database_host_id');
    }

    
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}
