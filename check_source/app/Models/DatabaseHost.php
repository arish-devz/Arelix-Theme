<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class DatabaseHost extends Model
{
    
    public const RESOURCE_NAME = 'database_host';

    protected bool $immutableDates = true;

    
    protected $table = 'database_hosts';

    
    protected $hidden = ['password'];

    
    protected $fillable = [
        'name', 'host', 'port', 'username', 'password', 'max_databases', 'node_id',
    ];

    
    protected $casts = [
        'id' => 'integer',
        'max_databases' => 'integer',
        'node_id' => 'integer',
    ];

    
    public static array $validationRules = [
        'name' => 'required|string|max:191',
        'host' => 'required|string',
        'port' => 'required|numeric|between:1,65535',
        'username' => 'required|string|max:32',
        'password' => 'nullable|string',
        'node_id' => 'sometimes|nullable|integer|exists:nodes,id',
    ];

    
    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class);
    }

    
    public function databases(): HasMany
    {
        return $this->hasMany(Database::class);
    }
}
