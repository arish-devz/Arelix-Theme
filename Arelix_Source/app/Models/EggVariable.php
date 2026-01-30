<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;


class EggVariable extends Model
{
    
    public const RESOURCE_NAME = 'egg_variable';

    
    public const RESERVED_ENV_NAMES = 'SERVER_MEMORY,SERVER_IP,SERVER_PORT,ENV,HOME,USER,STARTUP,SERVER_UUID,UUID';

    protected bool $immutableDates = true;

    
    protected $table = 'egg_variables';

    
    protected $guarded = ['id', 'created_at', 'updated_at'];

    
    protected $casts = [
        'egg_id' => 'integer',
        'user_viewable' => 'bool',
        'user_editable' => 'bool',
    ];

    public static array $validationRules = [
        'egg_id' => 'exists:eggs,id',
        'name' => 'required|string|between:1,191',
        'description' => 'string',
        'env_variable' => 'required|regex:/^[\w]{1,191}$/|notIn:' . self::RESERVED_ENV_NAMES,
        'default_value' => 'string',
        'user_viewable' => 'boolean',
        'user_editable' => 'boolean',
        'rules' => 'required|string',
    ];

    protected $attributes = [
        'user_editable' => 0,
        'user_viewable' => 0,
    ];

    public function getRequiredAttribute(): bool
    {
        return in_array('required', explode('|', $this->rules));
    }

    public function egg(): HasOne
    {
        return $this->hasOne(Egg::class);
    }

    
    public function serverVariable(): HasMany
    {
        return $this->hasMany(ServerVariable::class, 'variable_id');
    }
}
