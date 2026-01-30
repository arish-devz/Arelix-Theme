<?php
namespace Pterodactyl\Http\Requests\Admin;
use Pterodactyl\Models\User;
use Illuminate\Support\Collection;
class NewUserFormRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return Collection::make(
            User::getRules()
        )->only([
            'email',
            'username',
            'name_first',
            'name_last',
            'password',
            'language',
            'root_admin',
            'country',
            'address',
            'zip_code',
        ])->toArray();
    }
}
