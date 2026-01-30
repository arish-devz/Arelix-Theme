<?php

namespace Pterodactyl\Http\Requests\Admin;

use Pterodactyl\Models\Mount;

class MountFormRequest extends AdminFormRequest
{
    
    public function rules(): array
    {
        if ($this->method() === 'PATCH') {
            return Mount::getRulesForUpdate($this->route()->parameter('mount')->id);
        }

        return Mount::getRules();
    }
}
