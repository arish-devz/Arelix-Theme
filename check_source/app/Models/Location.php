<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;


class Location extends Model
{
    
    public const RESOURCE_NAME = 'location';

    
    protected $table = 'locations';

    
    protected $guarded = ['id', 'created_at', 'updated_at'];

    
    public static array $validationRules = [
        'short' => 'required|string|between:1,60|unique:locations,short',
        'long' => 'string|nullable|between:1,191',
    ];

    
    public function getRouteKeyName(): string
    {
        return $this->getKeyName();
    }

    
    public function nodes(): HasMany
    {
        return $this->hasMany(Node::class);
    }

    
    public function servers(): HasManyThrough
    {
        return $this->hasManyThrough(Server::class, Node::class);
    }
}
