<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;


class RecoveryToken extends Model
{
    
    public const UPDATED_AT = null;

    public $timestamps = true;

    protected bool $immutableDates = true;

    public static array $validationRules = [
        'token' => 'required|string',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
