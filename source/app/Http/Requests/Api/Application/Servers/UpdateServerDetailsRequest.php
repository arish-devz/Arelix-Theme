<?php
namespace Pterodactyl\Http\Requests\Api\Application\Servers;
use Pterodactyl\Models\Server;
class UpdateServerDetailsRequest extends ServerWriteRequest
{
    public function rules(): array
    {
        $rules = Server::getRulesForUpdate($this->parameter('server', Server::class));
        return [
            'external_id' => $rules['external_id'],
            'name' => $rules['name'],
            'user' => $rules['owner_id'],
            'description' => array_merge(['nullable'], $rules['description']),
            'exp_date' => 'sometimes|nullable|date',
        ];
    }
    public function validated($key = null, $default = null): array
    {
        return [
            'external_id' => $this->input('external_id'),
            'name' => $this->input('name'),
            'owner_id' => $this->input('user'),
            'description' => $this->input('description'),
            'exp_date' => $this->input('exp_date'),
        ];
    }
    public function attributes(): array
    {
        return [
            'user' => 'User ID',
            'name' => 'Server Name',
        ];
    }
}
