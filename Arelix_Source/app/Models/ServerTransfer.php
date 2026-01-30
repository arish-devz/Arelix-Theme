<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class ServerTransfer extends Model
{
    
    public const RESOURCE_NAME = 'server_transfer';

    
    protected $table = 'server_transfers';

    
    protected $guarded = ['id', 'created_at', 'updated_at'];

    
    protected $casts = [
        'server_id' => 'int',
        'old_node' => 'int',
        'new_node' => 'int',
        'old_allocation' => 'int',
        'new_allocation' => 'int',
        'old_additional_allocations' => 'array',
        'new_additional_allocations' => 'array',
        'successful' => 'bool',
        'archived' => 'bool',
    ];

    public static array $validationRules = [
        'server_id' => 'required|numeric|exists:servers,id',
        'old_node' => 'required|numeric',
        'new_node' => 'required|numeric',
        'old_allocation' => 'required|numeric',
        'new_allocation' => 'required|numeric',
        'old_additional_allocations' => 'nullable|array',
        'old_additional_allocations.*' => 'numeric',
        'new_additional_allocations' => 'nullable|array',
        'new_additional_allocations.*' => 'numeric',
        'successful' => 'sometimes|nullable|boolean',
    ];

    
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    
    public function oldNode(): HasOne
    {
        return $this->hasOne(Node::class, 'id', 'old_node');
    }

    
    public function newNode(): HasOne
    {
        return $this->hasOne(Node::class, 'id', 'new_node');
    }
}
