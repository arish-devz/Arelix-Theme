<?php

namespace Pterodactyl\Http\Requests\Admin;

use Pterodactyl\Models\Location;

class LocationFormRequest extends AdminFormRequest
{
    
    public function rules(): array
    {
        if ($this->method() === 'PATCH') {
            return Location::getRulesForUpdate($this->route()->parameter('location')->id);
        }

        return Location::getRules();
    }
}
