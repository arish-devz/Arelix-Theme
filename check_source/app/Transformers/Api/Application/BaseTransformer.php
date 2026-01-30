<?php

namespace Pterodactyl\Transformers\Api\Application;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Webmozart\Assert\Assert;
use Pterodactyl\Models\ApiKey;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use League\Fractal\TransformerAbstract;
use Pterodactyl\Services\Acl\Api\AdminAcl;


abstract class BaseTransformer extends TransformerAbstract
{
    public const RESPONSE_TIMEZONE = 'UTC';

    protected Request $request;

    
    public function __construct()
    {
        
        if (method_exists($this, 'handle')) {
            Container::getInstance()->call([$this, 'handle']);
        }
    }

    
    abstract public function getResourceName(): string;

    
    public function setRequest(Request $request): self
    {
        $this->request = $request;

        return $this;
    }

    
    public static function fromRequest(Request $request): BaseTransformer
    {
        return app(static::class)->setRequest($request);
    }

    
    protected function authorize(string $resource): bool
    {
        $allowed = [ApiKey::TYPE_ACCOUNT, ApiKey::TYPE_APPLICATION];

        $token = $this->request->user()->currentAccessToken();
        if (!$token instanceof ApiKey || !in_array($token->key_type, $allowed)) {
            return false;
        }

        
        
        
        if ($token->key_type === ApiKey::TYPE_ACCOUNT) {
            return $this->request->user()->root_admin;
        }

        return AdminAcl::check($token, $resource);
    }

    
    protected function makeTransformer(string $abstract)
    {
        Assert::subclassOf($abstract, self::class);

        return $abstract::fromRequest($this->request);
    }

    
    protected function formatTimestamp(string $timestamp): string
    {
        return CarbonImmutable::createFromFormat(CarbonInterface::DEFAULT_TO_STRING_FORMAT, $timestamp)
            ->setTimezone(self::RESPONSE_TIMEZONE)
            ->toAtomString();
    }
}
