<?php

namespace Pterodactyl\Http\Requests\Admin;

use Pterodactyl\Models\DatabaseHost;
use Illuminate\Contracts\Validation\Validator;

class DatabaseHostFormRequest extends AdminFormRequest
{
    public function rules(): array
    {
        if ($this->method() !== 'POST') {
            return DatabaseHost::getRulesForUpdate($this->route()->parameter('host'));
        }

        return DatabaseHost::getRules();
    }

    
    protected function getValidatorInstance(): Validator
    {
        if (!$this->filled('node_id')) {
            $this->merge(['node_id' => null]);
        }

        return parent::getValidatorInstance();
    }
}
