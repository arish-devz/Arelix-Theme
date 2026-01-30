<?php
namespace Pterodactyl\Transformers\Api\Client;
use Pterodactyl\Models\User;
class AccountTransformer extends BaseClientTransformer
{
    public function getResourceName(): string
    {
        return 'user';
    }
    public function transform(User $model): array
    {
        return [
            'id' => $model->id,
            'admin' => $model->root_admin,
            'username' => $model->username,
            'email' => $model->email,
            'first_name' => $model->name_first,
            'last_name' => $model->name_last,
            'language' => $model->language,
            'country' => $model->country,
            'address' => $model->address,
            'zip_code' => $model->zip_code,
        ];
    }
}
