<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Model;

class APILog extends Model
{
    
    protected $table = 'api_logs';

    
    protected $hidden = [];

    
    protected $guarded = ['id', 'created_at', 'updated_at'];

    
    protected $casts = [
        'authorized' => 'boolean',
    ];
}
