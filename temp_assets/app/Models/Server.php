<?php
namespace Pterodactyl\Models;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Query\JoinClause;
use Znck\Eloquent\Traits\BelongsToThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Pterodactyl\Exceptions\Http\Server\ServerStateConflictException;
class Server extends Model
{
    use BelongsToThrough;
    use Notifiable;
    public const RESOURCE_NAME = 'server';
    public const STATUS_INSTALLING = 'installing';
    public const STATUS_INSTALL_FAILED = 'install_failed';
    public const STATUS_REINSTALL_FAILED = 'reinstall_failed';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_RESTORING_BACKUP = 'restoring_backup';
    protected $table = 'servers';
    protected $attributes = [
        'status' => self::STATUS_INSTALLING,
        'oom_disabled' => true,
        'installed_at' => null,
    ];
    protected $with = ['allocation'];
    public $is_splitting = false;
    protected $guarded = ['id', self::CREATED_AT, self::UPDATED_AT, 'deleted_at', 'installed_at'];
    public static array $validationRules = [
        'external_id' => 'sometimes|nullable|string|between:1,191|unique:servers',
        'owner_id' => 'required|integer|exists:users,id',
        'name' => 'required|string|min:1|max:191',
        'node_id' => 'required|exists:nodes,id',
        'description' => 'string',
        'status' => 'nullable|string',
        'memory' => 'required|numeric|min:0',
        'swap' => 'required|numeric|min:-1',
        'io' => 'required|numeric|between:10,1000',
        'cpu' => 'required|numeric|min:0',
        'threads' => 'nullable|regex:/^[0-9-,]+$/',
        'oom_disabled' => 'sometimes|boolean',
        'disk' => 'required|numeric|min:0',
        'allocation_id' => 'required|bail|unique:servers|exists:allocations,id',
        'nest_id' => 'required|exists:nests,id',
        'egg_id' => 'required|exists:eggs,id',
        'startup' => 'required|string',
        'skip_scripts' => 'sometimes|boolean',
        'image' => ['required', 'string', 'max:191', 'regex:/^[\w\.\/\-:@ ]*$/'],
        'database_limit' => 'present|nullable|integer|min:0',
        'allocation_limit' => 'sometimes|nullable|integer|min:0',
        'backup_limit' => 'present|nullable|integer|min:0',
    ];
    protected $casts = [
        'node_id' => 'integer',
        'skip_scripts' => 'boolean',
        'owner_id' => 'integer',
        'memory' => 'integer',
        'swap' => 'integer',
        'disk' => 'integer',
        'io' => 'integer',
        'cpu' => 'integer',
        'oom_disabled' => 'boolean',
        'allocation_id' => 'integer',
        'nest_id' => 'integer',
        'egg_id' => 'integer',
        'database_limit' => 'integer',
        'allocation_limit' => 'integer',
        'backup_limit' => 'integer',
        'masterserver' => 'string',
        self::CREATED_AT => 'datetime',
        self::UPDATED_AT => 'datetime',
        'deleted_at' => 'datetime',
        'installed_at' => 'datetime',
        'exp_date' => 'date',
        'meta' => 'array',
    ];
    public function getAllocationMappings(): array
    {
        return $this->allocations->where('node_id', $this->node_id)->groupBy('ip')->map(function ($item) {
            return $item->pluck('port');
        })->toArray();
    }
    public function isInstalled(): bool
    {
        return $this->status !== self::STATUS_INSTALLING && $this->status !== self::STATUS_INSTALL_FAILED;
    }
    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
    public function subusers(): HasMany
    {
        return $this->hasMany(Subuser::class, 'server_id', 'id');
    }
    public function allocation(): HasOne
    {
        return $this->hasOne(Allocation::class, 'id', 'allocation_id');
    }
    public function allocations(): HasMany
    {
        return $this->hasMany(Allocation::class, 'server_id');
    }
    public function nest(): BelongsTo
    {
        return $this->belongsTo(Nest::class);
    }
    public function egg(): HasOne
    {
        return $this->hasOne(Egg::class, 'id', 'egg_id');
    }
    public function variables(): HasMany
    {
        return $this->hasMany(EggVariable::class, 'egg_id', 'egg_id')
            ->select(['egg_variables.*', 'server_variables.variable_value as server_value'])
            ->leftJoin('server_variables', function (JoinClause $join) {
                $join->on('server_variables.variable_id', 'egg_variables.id')
                    ->where('server_variables.server_id', $this->id);
            });
    }
    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class);
    }
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }
    public function databases(): HasMany
    {
        return $this->hasMany(Database::class);
    }
    public function imports(): HasMany
    {
        return $this->hasMany(ServerImport::class);
    }
    public function splits(): HasMany
    {
        return $this->hasMany(ServerSplit::class, 'master_server_id');
    }
    public function subSplit(): HasOne
    {
        return $this->hasOne(ServerSplit::class, 'sub_server_id');
    }
    public function wipeSchedules(): HasMany
    {
        return $this->hasMany(WipeSchedule::class);
    }
    public function wipeExecutions(): HasMany
    {
        return $this->hasMany(WipeExecution::class);
    }
    public function rustMapLibrary(): HasMany
    {
        return $this->hasMany(RustMapLibrary::class);
    }
    public function location(): \Znck\Eloquent\Relations\BelongsToThrough
    {
        return $this->belongsToThrough(Location::class, Node::class);
    }
    public function transfer(): HasOne
    {
        return $this->hasOne(ServerTransfer::class)->whereNull('successful')->orderByDesc('id');
    }
    public function backups(): HasMany
    {
        return $this->hasMany(Backup::class);
    }
    public function mounts(): HasManyThrough
    {
        return $this->hasManyThrough(Mount::class, MountServer::class, 'server_id', 'id', 'id', 'mount_id');
    }
    public function activity(): MorphToMany
    {
        return $this->morphToMany(ActivityLog::class, 'subject', 'activity_log_subjects');
    }
    public function proxies(): HasMany
    {
        return $this->hasMany(ReverseProxy::class);
    }
    public function validateCurrentState()
    {
        if (
            $this->isSuspended() ||
            $this->node->isUnderMaintenance() ||
            !$this->isInstalled() ||
            $this->status === self::STATUS_RESTORING_BACKUP ||
            !is_null($this->transfer)
        ) {
            throw new ServerStateConflictException($this);
        }
    }
    public function validateTransferState()
    {
        if (
            !$this->isInstalled() ||
            $this->status === self::STATUS_RESTORING_BACKUP ||
            !is_null($this->transfer)
        ) {
            throw new ServerStateConflictException($this);
        }
    }
    public function resolveRouteBinding($value, $field = null)
    {
        if ($field === 'uuid' || $field === null) {
            return $this->where('uuid', $value)->first();
        }
        if ($field === 'id') {
            return $this->where('id', $value)->first();
        }
        return parent::resolveRouteBinding($value, $field);
    }
}
