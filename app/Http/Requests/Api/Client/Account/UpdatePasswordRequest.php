<?php

namespace Pterodactyl\Http\Requests\Api\Client\Account;

use Illuminate\Container\Container;
use Illuminate\Contracts\Hashing\Hasher;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Pterodactyl\Exceptions\Http\Base\InvalidPasswordProvidedException;

class UpdatePasswordRequest extends ClientApiRequest
{
    
    public function authorize(): bool
    {
        if (!parent::authorize()) {
            return false;
        }

        $hasher = Container::getInstance()->make(Hasher::class);

        
        if (!$hasher->check($this->input('current_password'), $this->user()->password)) {
            throw new InvalidPasswordProvidedException(trans('validation.internal.invalid_password'));
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'confirmed', 'min:8'],
        ];
    }
}
