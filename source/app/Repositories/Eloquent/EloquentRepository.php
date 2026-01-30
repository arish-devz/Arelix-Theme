<?php

namespace Pterodactyl\Repositories\Eloquent;

use Illuminate\Http\Request;
use Webmozart\Assert\Assert;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Pterodactyl\Repositories\Repository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Pterodactyl\Contracts\Repository\RepositoryInterface;
use Pterodactyl\Exceptions\Model\DataValidationException;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;

abstract class EloquentRepository extends Repository implements RepositoryInterface
{
    protected bool $useRequestFilters = false;

    
    public function usingRequestFilters(bool $usingFilters = true): self
    {
        $this->useRequestFilters = $usingFilters;

        return $this;
    }

    
    protected function request(): Request
    {
        return $this->app->make(Request::class);
    }

    
    protected function paginate(Builder $instance, int $default = 50): LengthAwarePaginator
    {
        if (!$this->useRequestFilters) {
            return $instance->paginate($default);
        }

        return $instance->paginate($this->request()->query('per_page', $default));
    }

    
    public function getModel(): Model
    {
        return $this->model;
    }

    
    public function getBuilder(): Builder
    {
        return $this->getModel()->newQuery();
    }

    
    public function create(array $fields, bool $validate = true, bool $force = false): Model|bool
    {
        $instance = $this->getBuilder()->newModelInstance();
        ($force) ? $instance->forceFill($fields) : $instance->fill($fields);

        if (!$validate) {
            $saved = $instance->skipValidation()->save();
        } else {
            if (!$saved = $instance->save()) {
                throw new DataValidationException($instance->getValidator(), $instance);
            }
        }

        return ($this->withFresh) ? $instance->fresh() : $saved;
    }

    
    public function find(int $id): Model
    {
        try {
            return $this->getBuilder()->findOrFail($id, $this->getColumns());
        } catch (ModelNotFoundException) {
            throw new RecordNotFoundException();
        }
    }

    
    public function findWhere(array $fields): Collection
    {
        return $this->getBuilder()->where($fields)->get($this->getColumns());
    }

    
    public function findFirstWhere(array $fields): Model
    {
        try {
            return $this->getBuilder()->where($fields)->firstOrFail($this->getColumns());
        } catch (ModelNotFoundException) {
            throw new RecordNotFoundException();
        }
    }

    
    public function findCountWhere(array $fields): int
    {
        return $this->getBuilder()->where($fields)->count($this->getColumns());
    }

    
    public function delete(int $id, bool $destroy = false): int
    {
        return $this->deleteWhere(['id' => $id], $destroy);
    }

    
    public function deleteWhere(array $attributes, bool $force = false): int
    {
        $instance = $this->getBuilder()->where($attributes);

        return ($force) ? $instance->forceDelete() : $instance->delete();
    }

    
    public function update(int $id, array $fields, bool $validate = true, bool $force = false): Model|bool
    {
        try {
            $instance = $this->getBuilder()->where('id', $id)->firstOrFail();
        } catch (ModelNotFoundException) {
            throw new RecordNotFoundException();
        }

        ($force) ? $instance->forceFill($fields) : $instance->fill($fields);

        if (!$validate) {
            $saved = $instance->skipValidation()->save();
        } else {
            if (!$saved = $instance->save()) {
                throw new DataValidationException($instance->getValidator(), $instance);
            }
        }

        return ($this->withFresh) ? $instance->fresh() : $saved;
    }

    
    public function updateWhere(array $attributes, array $values): int
    {
        return $this->getBuilder()->where($attributes)->update($values);
    }

    
    public function updateWhereIn(string $column, array $values, array $fields): int
    {
        Assert::notEmpty($column, 'First argument passed to updateWhereIn must be a non-empty string.');

        return $this->getBuilder()->whereIn($column, $values)->update($fields);
    }

    
    public function updateOrCreate(array $where, array $fields, bool $validate = true, bool $force = false): Model|bool
    {
        foreach ($where as $item) {
            Assert::true(is_scalar($item) || is_null($item), 'First argument passed to updateOrCreate should be an array of scalar or null values, received an array value of %s.');
        }

        try {
            $instance = $this->setColumns('id')->findFirstWhere($where);
        } catch (RecordNotFoundException) {
            return $this->create(array_merge($where, $fields), $validate, $force);
        }

        return $this->update($instance->id, $fields, $validate, $force);
    }

    
    public function all(): Collection
    {
        return $this->getBuilder()->get($this->getColumns());
    }

    
    public function paginated(int $perPage): LengthAwarePaginator
    {
        return $this->getBuilder()->paginate($perPage, $this->getColumns());
    }

    
    public function insert(array $data): bool
    {
        return $this->getBuilder()->insert($data);
    }

    
    public function insertIgnore(array $values): bool
    {
        if (empty($values)) {
            return true;
        }

        foreach ($values as $key => $value) {
            ksort($value);
            $values[$key] = $value;
        }

        $bindings = array_values(array_filter(array_flatten($values, 1), function ($binding) {
            return !$binding instanceof Expression;
        }));

        $grammar = $this->getBuilder()->toBase()->getGrammar();
        $table = $grammar->wrapTable($this->getModel()->getTable());
        $columns = $grammar->columnize(array_keys(reset($values)));

        $parameters = collect($values)->map(function ($record) use ($grammar) {
            return sprintf('(%s)', $grammar->parameterize($record));
        })->implode(', ');

        $statement = "insert ignore into $table ($columns) values $parameters";

        return $this->getBuilder()->getConnection()->statement($statement, $bindings);
    }

    
    public function count(): int
    {
        return $this->getBuilder()->count();
    }
}
