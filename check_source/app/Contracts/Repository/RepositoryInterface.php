<?php

namespace Pterodactyl\Contracts\Repository;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface RepositoryInterface
{
    
    public function model(): string;

    
    public function getModel(): Model;

    
    public function getBuilder(): Builder;

    
    public function getColumns(): array;

    
    public function setColumns(array|string $columns = ['*']): self;

    
    public function withoutFreshModel(): self;

    
    public function withFreshModel(): self;

    
    public function setFreshModel(bool $fresh = true): self;

    
    public function create(array $fields, bool $validate = true, bool $force = false): mixed;

    
    public function find(int $id): mixed;

    
    public function findWhere(array $fields): Collection;

    
    public function findFirstWhere(array $fields): mixed;

    
    public function findCountWhere(array $fields): int;

    
    public function delete(int $id): int;

    
    public function deleteWhere(array $attributes): int;

    
    public function update(int $id, array $fields, bool $validate = true, bool $force = false): mixed;

    
    public function updateWhereIn(string $column, array $values, array $fields): int;

    
    public function updateOrCreate(array $where, array $fields, bool $validate = true, bool $force = false): mixed;

    
    public function all(): Collection;

    
    public function paginated(int $perPage): LengthAwarePaginator;

    
    public function insert(array $data): bool;

    
    public function insertIgnore(array $values): bool;

    
    public function count(): int;
}
