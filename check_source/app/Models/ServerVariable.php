<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;


class ServerVariable extends Model
{
    
    public const RESOURCE_NAME = 'server_variable';

    protected bool $immutableDates = true;

    protected $table = 'server_variables';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'server_id' => 'integer',
        'variable_id' => 'integer',
    ];

    public static array $validationRules = [
        'server_id' => 'required|int',
        'variable_id' => 'required|int',
        'variable_value' => 'string',
    ];

    
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    
    public function variable(): BelongsTo
    {
        return $this->belongsTo(EggVariable::class, 'variable_id');
    }
}
