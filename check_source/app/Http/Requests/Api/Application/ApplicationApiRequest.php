<?php

namespace Pterodactyl\Http\Requests\Api\Application;

use Webmozart\Assert\Assert;
use Pterodactyl\Models\ApiKey;
use Laravel\Sanctum\TransientToken;
use Illuminate\Validation\Validator;
use Illuminate\Database\Eloquent\Model;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Illuminate\Foundation\Http\FormRequest;
use Pterodactyl\Exceptions\PterodactylException;

abstract class ApplicationApiRequest extends FormRequest
{
    
    protected ?string $resource;

    
    protected int $permission = AdminAcl::NONE;

    
    public function authorize(): bool
    {
        if (is_null($this->resource)) {
            throw new PterodactylException('An ACL resource must be defined on API requests.');
        }

        $token = $this->user()->currentAccessToken();
        if ($token instanceof TransientToken) {
            return true;
        }

        if ($token->key_type === ApiKey::TYPE_ACCOUNT) {
            return true;
        }

        return AdminAcl::check($token, $this->resource, $this->permission);
    }

    
    public function rules(): array
    {
        return [];
    }

    
    public function withValidator(Validator $validator): void
    {
        
    }

    
    public function parameter(string $key, string $expect)
    {
        $value = $this->route()->parameter($key);

        Assert::isInstanceOf($value, $expect);
        Assert::isInstanceOf($value, Model::class);
        Assert::true($value->exists);

        
        return $value;
    }
}
