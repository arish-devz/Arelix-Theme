<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Model;

class TaskLog extends Model
{
    
    protected $table = 'tasks_log';

    
    protected $guarded = ['id', 'created_at', 'updated_at'];

    
    protected $casts = [
        'id' => 'integer',
        'task_id' => 'integer',
        'run_status' => 'integer',
        'run_time' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
