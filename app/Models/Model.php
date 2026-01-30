<?php

namespace Pterodactyl\Models;

use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Container\Container;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Pterodactyl\Exceptions\Model\DataValidationException;
use Illuminate\Database\Eloquent\Model as IlluminateModel;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;

abstract class Model extends IlluminateModel
{
    use HasFactory;

    
    protected bool $immutableDates = false;

    
    protected bool $skipValidation = false;

    protected static ValidationFactory $validatorFactory;

    public static array $validationRules = [];

    
    protected static function boot()
    {
        parent::boot();

        static::$validatorFactory = Container::getInstance()->make(ValidationFactory::class);

        static::saving(function (Model $model) {
            try {
                $model->validate();
            } catch (ValidationException $exception) {
                throw new DataValidationException($exception->validator, $model);
            }

            return true;
        });
    }

    
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    
    public function skipValidation(): self
    {
        $this->skipValidation = true;

        return $this;
    }

    
    public function getValidator(): Validator
    {
        $rules = $this->exists ? static::getRulesForUpdate($this) : static::getRules();

        return static::$validatorFactory->make([], $rules, [], []);
    }

    
    public static function getRules(): array
    {
        $rules = static::$validationRules;
        foreach ($rules as $key => &$rule) {
            $rule = is_array($rule) ? $rule : explode('|', $rule);
        }

        return $rules;
    }

    
    public static function getRulesForField(string $field): array
    {
        return Arr::get(static::getRules(), $field) ?? [];
    }

    
    public static function getRulesForUpdate($model, string $column = 'id'): array
    {
        if ($model instanceof Model) {
            [$id, $column] = [$model->getKey(), $model->getKeyName()];
        }

        $rules = static::getRules();
        foreach ($rules as $key => &$data) {
            
            
            
            
            foreach ($data as &$datum) {
                if (!is_string($datum) || !Str::startsWith($datum, 'unique')) {
                    continue;
                }

                [, $args] = explode(':', $datum);
                $args = explode(',', $args);

                $datum = Rule::unique($args[0], $args[1] ?? $key)->ignore($id ?? $model, $column);
            }
        }

        return $rules;
    }

    
    public function validate(): void
    {
        if ($this->skipValidation) {
            return;
        }

        $validator = $this->getValidator();
        $validator->setData(
            
            
            
            $this->addCastAttributesToArray(
                $this->getAttributes(),
                $this->getMutatedAttributes()
            )
        );

        if (!$validator->passes()) {
            throw new ValidationException($validator);
        }
    }

    
    protected function asDateTime($value): Carbon|CarbonImmutable
    {
        if (!$this->immutableDates) {
            return parent::asDateTime($value);
        }

        return parent::asDateTime($value)->toImmutable();
    }
}
