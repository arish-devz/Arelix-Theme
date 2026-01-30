<?php

namespace Pterodactyl\Models;

use Illuminate\Validation\Rules\NotIn;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class Mount extends Model
{
    
    public const RESOURCE_NAME = 'mount';

    
    protected $table = 'mounts';

    
    protected $guarded = ['id', 'uuid'];

    
    protected $casts = [
        'id' => 'int',
        'read_only' => 'bool',
        'user_mountable' => 'bool',
    ];

    
    public static array $validationRules = [
        'name' => 'required|string|min:2|max:64|unique:mounts,name',
        'description' => 'nullable|string|max:191',
        'source' => 'required|string',
        'target' => 'required|string',
        'read_only' => 'sometimes|boolean',
        'user_mountable' => 'sometimes|boolean',
    ];

    
    public static function getRules(): array
    {
        $rules = parent::getRules();

        $rules['source'][] = new NotIn(Mount::$invalidSourcePaths);
        $rules['target'][] = new NotIn(Mount::$invalidTargetPaths);

        return $rules;
    }

    
    public $timestamps = false;

    
    public static $invalidSourcePaths = [
        '/etc/pterodactyl',
        '/var/lib/pterodactyl/volumes',
        '/srv/daemon-data',
    ];

    
    public static $invalidTargetPaths = [
        '/home/container',
    ];

    
    public function eggs(): BelongsToMany
    {
        return $this->belongsToMany(Egg::class);
    }

    
    public function nodes(): BelongsToMany
    {
        return $this->belongsToMany(Node::class);
    }

    
    public function servers(): BelongsToMany
    {
        return $this->belongsToMany(Server::class);
    }
}
