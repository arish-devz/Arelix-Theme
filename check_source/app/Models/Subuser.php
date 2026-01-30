<?php

namespace Pterodactyl\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class Subuser extends Model
{
    use Notifiable;

    
    public const RESOURCE_NAME = 'server_subuser';

    
    protected $table = 'subusers';

    
    protected $guarded = ['id', 'created_at', 'updated_at'];

    
    protected $casts = [
        'user_id' => 'int',
        'server_id' => 'int',
        'permissions' => 'array',
    ];

    public static array $validationRules = [
        'user_id' => 'required|numeric|exists:users,id',
        'server_id' => 'required|numeric|exists:servers,id',
        'permissions' => 'nullable|array',
        'permissions.*' => 'string',
    ];

    
    public function getHashidAttribute(): string
    {
        return app()->make('hashids')->encode($this->id);
    }

    
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    
    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class);
    }
}
