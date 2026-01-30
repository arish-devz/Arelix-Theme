<?php

namespace Pterodactyl\Http\Requests\Api\Remote;

use Illuminate\Foundation\Http\FormRequest;

class SftpAuthenticationFormRequest extends FormRequest
{
    
    public function authorize(): bool
    {
        return true;
    }

    
    public function rules(): array
    {
        return [
            'type' => ['nullable', 'in:password,public_key'],
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    
    public function normalize(): array
    {
        return $this->only(
            array_keys($this->rules())
        );
    }
}
