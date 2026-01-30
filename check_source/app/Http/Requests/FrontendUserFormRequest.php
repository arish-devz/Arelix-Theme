<?php

namespace Pterodactyl\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class FrontendUserFormRequest extends FormRequest
{
    abstract public function rules(): array;

    
    public function authorize(): bool
    {
        return !is_null($this->user());
    }

    
    public function normalize(): array
    {
        return $this->only(
            array_keys($this->rules())
        );
    }
}
