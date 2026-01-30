<?php

namespace Pterodactyl\Rules;

use Illuminate\Support\Arr;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\DataAwareRule;

class Fqdn implements Rule, DataAwareRule
{
    protected array $data = [];
    protected string $message = '';
    protected ?string $schemeField = null;

    
    public function setData($data): self
    {
        $this->data = $data;

        return $this;
    }

    
    public function passes($attribute, $value): bool
    {
        if (filter_var($value, FILTER_VALIDATE_IP)) {
            
            
            
            
            
            if ($this->schemeField && Arr::get($this->data, $this->schemeField) === 'https') {
                $this->message = 'The :attribute must not be an IP address when HTTPS is enabled.';

                return false;
            }

            return true;
        }

        
        
        
        
        $records = @dns_get_record($value, DNS_A + DNS_AAAA);
        
        
        
        if (!empty($records) || filter_var(gethostbyname($value), FILTER_VALIDATE_IP)) {
            return true;
        }

        $this->message = 'The :attribute could not be resolved to a valid IP address.';

        return false;
    }

    public function message(): string
    {
        return $this->message;
    }

    
    public static function make(string $schemeField = null): self
    {
        return tap(new static(), function ($fqdn) use ($schemeField) {
            $fqdn->schemeField = $schemeField;
        });
    }
}
