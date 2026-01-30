<?php

namespace Pterodactyl\Repositories;

use Illuminate\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use Pterodactyl\Contracts\Repository\RepositoryInterface;

abstract class Repository implements RepositoryInterface
{
    protected array $columns = ['*'];

    protected Model $model;

    protected bool $withFresh = true;

    
    public function __construct(protected Application $app)
    {
        $this->initializeModel($this->model());
    }

    
    abstract public function model(): string;

    
    public function getModel(): Model
    {
        return $this->model;
    }

    
    public function setColumns($columns = ['*']): self
    {
        $clone = clone $this;
        $clone->columns = is_array($columns) ? $columns : func_get_args();

        return $clone;
    }

    
    public function getColumns(): array
    {
        return $this->columns;
    }

    
    public function withoutFreshModel(): self
    {
        return $this->setFreshModel(false);
    }

    
    public function withFreshModel(): self
    {
        return $this->setFreshModel();
    }

    
    public function setFreshModel(bool $fresh = true): self
    {
        $clone = clone $this;
        $clone->withFresh = $fresh;

        return $clone;
    }

    
    protected function initializeModel(string ...$model): mixed
    {
        switch (count($model)) {
            case 1:
                return $this->model = $this->app->make($model[0]);
            case 2:
                return $this->model = call_user_func([$this->app->make($model[0]), $model[1]]);
            default:
                throw new \InvalidArgumentException('Model must be a FQDN or an array with a count of two.');
        }
    }
}
