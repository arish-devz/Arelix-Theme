<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;


class Allocation extends Model
{
    
    public const RESOURCE_NAME = 'allocation';

    
    protected $table = 'allocations';

    
    protected $guarded = ['id', 'created_at', 'updated_at'];

    
    protected $casts = [
        'node_id' => 'integer',
        'port' => 'integer',
        'server_id' => 'integer',
    ];

    public static array $validationRules = [
        'node_id' => 'required|exists:nodes,id',
        'ip' => 'required|ip',
        'port' => 'required|numeric|between:1024,65535',
        'ip_alias' => 'nullable|string',
        'server_id' => 'nullable|exists:servers,id',
        'notes' => 'nullable|string|max:256',
    ];

    
    public function getRouteKeyName(): string
    {
        return $this->getKeyName();
    }

    
    public function getHashidAttribute(): string
    {
        return app()->make('hashids')->encode($this->id);
    }

    
    public function getAliasAttribute(?string $value): string
    {
        return (is_null($this->ip_alias)) ? $this->ip : $this->ip_alias;
    }

    
    public function getHasAliasAttribute(?string $value): bool
    {
        return !is_null($this->ip_alias);
    }

    public function toString(): string
    {
        return sprintf('%s:%s', $this->ip, $this->port);
    }

    
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    
    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class);
    }
}
