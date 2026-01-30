<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;


class Nest extends Model
{
    
    public const RESOURCE_NAME = 'nest';

    
    protected $table = 'nests';

    
    protected $fillable = [
        'name',
        'description',
    ];

    public static array $validationRules = [
        'author' => 'required|string|email',
        'name' => 'required|string|max:191',
        'description' => 'nullable|string',
    ];

    
    public function eggs(): HasMany
    {
        return $this->hasMany(Egg::class);
    }

    
    public function servers(): HasMany
    {
        return $this->hasMany(Server::class);
    }
}
