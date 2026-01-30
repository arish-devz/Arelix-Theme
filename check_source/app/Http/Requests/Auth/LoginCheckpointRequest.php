<?php

namespace Pterodactyl\Http\Requests\Auth;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class LoginCheckpointRequest extends FormRequest
{
    
    public function authorize(): bool
    {
        return true;
    }

    
    public function rules(): array
    {
        return [
            'confirmation_token' => 'required|string',
            'authentication_code' => [
                'nullable',
                'numeric',
                Rule::requiredIf(function () {
                    return empty($this->input('recovery_token'));
                }),
            ],
            'recovery_token' => [
                'nullable',
                'string',
                Rule::requiredIf(function () {
                    return empty($this->input('authentication_code'));
                }),
            ],
        ];
    }
}
