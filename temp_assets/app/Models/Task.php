<?php

namespace Pterodactyl\Models;

use Illuminate\Container\Container;
use Znck\Eloquent\Traits\BelongsToThrough;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Pterodactyl\Contracts\Extensions\HashidsInterface;


class Task extends Model
{
    use BelongsToThrough;

    
    public const RESOURCE_NAME = 'schedule_task';

    
    public const ACTION_POWER = 'power';
    public const ACTION_COMMAND = 'command';
    public const ACTION_BACKUP = 'backup';

    
    protected $table = 'tasks';

    
    protected $touches = ['schedule'];

    
    protected $fillable = [
        'schedule_id',
        'sequence_id',
        'action',
        'payload',
        'time_offset',
        'is_queued',
        'continue_on_failure',
    ];

    
    protected $casts = [
        'id' => 'integer',
        'schedule_id' => 'integer',
        'sequence_id' => 'integer',
        'time_offset' => 'integer',
        'is_queued' => 'boolean',
        'continue_on_failure' => 'boolean',
    ];

    
    protected $attributes = [
        'time_offset' => 0,
        'is_queued' => false,
        'continue_on_failure' => false,
    ];

    public static array $validationRules = [
        'schedule_id' => 'required|numeric|exists:schedules,id',
        'sequence_id' => 'required|numeric|min:1',
        'action' => 'required|string',
        'payload' => 'required_unless:action,backup|string',
        'time_offset' => 'required|numeric|between:0,900',
        'is_queued' => 'boolean',
        'continue_on_failure' => 'boolean',
    ];

    
    public function getRouteKeyName(): string
    {
        return $this->getKeyName();
    }

    
    public function getHashidAttribute(): string
    {
        return Container::getInstance()->make(HashidsInterface::class)->encode($this->id);
    }

    
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    
    public function server(): \Znck\Eloquent\Relations\BelongsToThrough
    {
        return $this->belongsToThrough(Server::class, Schedule::class);
    }
}
