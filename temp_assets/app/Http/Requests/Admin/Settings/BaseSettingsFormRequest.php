<?php

namespace Pterodactyl\Http\Requests\Admin\Settings;

use Illuminate\Validation\Rule;
use Pterodactyl\Traits\Helpers\AvailableLanguages;
use Pterodactyl\Http\Requests\Admin\AdminFormRequest;

class BaseSettingsFormRequest extends AdminFormRequest
{
    use AvailableLanguages;

    public function rules(): array
    {
        return [
            'app:name' => 'required|string|max:191',
            'pterodactyl:auth:2fa_required' => 'required|integer|in:0,1,2',
            'app:locale' => ['required', 'string', Rule::in(array_keys($this->getAvailableLanguages()))],
            'app:theme' => 'required|string|in:default,hyperv1',
            'app:admin_theme' => 'required|string|in:default,hyperv1',
            'app:hyperv1_license' => 'nullable|string|max:255',
        ];
    }

    public function attributes(): array
    {
        return [
            'app:name' => 'Company Name',
            'pterodactyl:auth:2fa_required' => 'Require 2-Factor Authentication',
            'app:locale' => 'Default Language',
            'app:theme' => 'Client Theme',
            'app:admin_theme' => 'Admin Theme',
            'app:hyperv1_license' => 'HyperV1 License Key',
        ];
    }
}
