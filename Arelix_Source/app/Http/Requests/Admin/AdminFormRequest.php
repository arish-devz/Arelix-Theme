<?php
namespace Pterodactyl\Http\Requests\Admin;
use Illuminate\Foundation\Http\FormRequest;
abstract class AdminFormRequest extends FormRequest
{
    abstract public function rules(): array;
    public function authorize(): bool
    {
        return true;
    }
    public function normalize(array $only = null): array
    {
        return $this->only($only ?? array_keys($this->rules()));
    }
}
