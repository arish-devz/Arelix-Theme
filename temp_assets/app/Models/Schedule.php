<?php

namespace Pterodactyl\Models;

use Cron\CronExpression;
use Carbon\CarbonImmutable;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Pterodactyl\Contracts\Extensions\HashidsInterface;


class Schedule extends Model
{
    
    public const RESOURCE_NAME = 'server_schedule';

    
    protected $table = 'schedules';

    
    protected $with = ['tasks'];

    
    protected $fillable = [
        'server_id',
        'name',
        'cron_day_of_week',
        'cron_month',
        'cron_day_of_month',
        'cron_hour',
        'cron_minute',
        'is_active',
        'is_processing',
        'only_when_online',
        'last_run_at',
        'next_run_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'server_id' => 'integer',
        'is_active' => 'boolean',
        'is_processing' => 'boolean',
        'only_when_online' => 'boolean',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
    ];

    protected $attributes = [
        'name' => null,
        'cron_day_of_week' => '*',
        'cron_month' => '*',
        'cron_day_of_month' => '*',
        'cron_hour' => '*',
        'cron_minute' => '*',
        'is_active' => true,
        'is_processing' => false,
        'only_when_online' => false,
    ];

    public static array $validationRules = [
        'server_id' => 'required|exists:servers,id',
        'name' => 'required|string|max:191',
        'cron_day_of_week' => 'required|string',
        'cron_month' => 'required|string',
        'cron_day_of_month' => 'required|string',
        'cron_hour' => 'required|string',
        'cron_minute' => 'required|string',
        'is_active' => 'boolean',
        'is_processing' => 'boolean',
        'only_when_online' => 'boolean',
        'last_run_at' => 'nullable|date',
        'next_run_at' => 'nullable|date',
    ];

    
    public function getRouteKeyName(): string
    {
        return $this->getKeyName();
    }

    
    public function getNextRunDate(): CarbonImmutable
    {
        $formatted = sprintf('%s %s %s %s %s', $this->cron_minute, $this->cron_hour, $this->cron_day_of_month, $this->cron_month, $this->cron_day_of_week);

        return CarbonImmutable::createFromTimestamp(
            (new CronExpression($formatted))->getNextRunDate()->getTimestamp()
        );
    }

    
    public function getHashidAttribute(): string
    {
        return Container::getInstance()->make(HashidsInterface::class)->encode($this->id);
    }

    
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}
