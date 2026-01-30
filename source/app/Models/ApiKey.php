<?php

namespace Pterodactyl\Models;

use Illuminate\Support\Str;
use Webmozart\Assert\Assert;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class ApiKey extends Model
{
    
    public const RESOURCE_NAME = 'api_key';
    
    public const TYPE_NONE = 0;
    public const TYPE_ACCOUNT = 1;
    
    public const TYPE_APPLICATION = 2;
    
    public const TYPE_DAEMON_USER = 3;
    
    public const TYPE_DAEMON_APPLICATION = 4;
    
    public const IDENTIFIER_LENGTH = 16;
    
    public const KEY_LENGTH = 32;

    
    protected $table = 'api_keys';

    
    protected $casts = [
        'allowed_ips' => 'array',
        'user_id' => 'int',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        self::CREATED_AT => 'datetime',
        self::UPDATED_AT => 'datetime',
        'r_' . AdminAcl::RESOURCE_USERS => 'int',
        'r_' . AdminAcl::RESOURCE_ALLOCATIONS => 'int',
        'r_' . AdminAcl::RESOURCE_DATABASE_HOSTS => 'int',
        'r_' . AdminAcl::RESOURCE_SERVER_DATABASES => 'int',
        'r_' . AdminAcl::RESOURCE_EGGS => 'int',
        'r_' . AdminAcl::RESOURCE_LOCATIONS => 'int',
        'r_' . AdminAcl::RESOURCE_NESTS => 'int',
        'r_' . AdminAcl::RESOURCE_NODES => 'int',
        'r_' . AdminAcl::RESOURCE_SERVERS => 'int',
    ];

    
    protected $fillable = [
        'identifier',
        'token',
        'allowed_ips',
        'memo',
        'last_used_at',
        'expires_at',
    ];

    
    protected $hidden = ['token'];

    
    public static array $validationRules = [
        'user_id' => 'required|exists:users,id',
        'key_type' => 'present|integer|min:0|max:4',
        'identifier' => 'required|string|size:16|unique:api_keys,identifier',
        'token' => 'required|string',
        'memo' => 'required|nullable|string|max:500',
        'allowed_ips' => 'nullable|array',
        'allowed_ips.*' => 'string',
        'last_used_at' => 'nullable|date',
        'expires_at' => 'nullable|date',
        'r_' . AdminAcl::RESOURCE_USERS => 'integer|min:0|max:3',
        'r_' . AdminAcl::RESOURCE_ALLOCATIONS => 'integer|min:0|max:3',
        'r_' . AdminAcl::RESOURCE_DATABASE_HOSTS => 'integer|min:0|max:3',
        'r_' . AdminAcl::RESOURCE_SERVER_DATABASES => 'integer|min:0|max:3',
        'r_' . AdminAcl::RESOURCE_EGGS => 'integer|min:0|max:3',
        'r_' . AdminAcl::RESOURCE_LOCATIONS => 'integer|min:0|max:3',
        'r_' . AdminAcl::RESOURCE_NESTS => 'integer|min:0|max:3',
        'r_' . AdminAcl::RESOURCE_NODES => 'integer|min:0|max:3',
        'r_' . AdminAcl::RESOURCE_SERVERS => 'integer|min:0|max:3',
    ];

    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    
    public function tokenable(): BelongsTo
    {
        return $this->user();
    }

    
    public static function findToken(string $token): ?self
    {
        $identifier = substr($token, 0, self::IDENTIFIER_LENGTH);

        $model = static::where('identifier', $identifier)->first();
        if (!is_null($model) && decrypt($model->token) === substr($token, strlen($identifier))) {
            return $model;
        }

        return null;
    }

    
    public static function getPrefixForType(int $type): string
    {
        Assert::oneOf($type, [self::TYPE_ACCOUNT, self::TYPE_APPLICATION]);

        return $type === self::TYPE_ACCOUNT ? 'ptlc_' : 'ptla_';
    }

    
    public static function generateTokenIdentifier(int $type): string
    {
        $prefix = self::getPrefixForType($type);

        return $prefix . Str::random(self::IDENTIFIER_LENGTH - strlen($prefix));
    }
}
