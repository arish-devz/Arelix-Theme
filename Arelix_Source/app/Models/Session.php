<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    
    protected $table = 'sessions';

    
    protected $casts = [
        'id' => 'string',
        'user_id' => 'integer',
    ];
}
