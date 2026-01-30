<?php
namespace Pterodactyl\Models\Filters;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
class MultiFieldServerFilter implements Filter
{
    private const IPV4_REGEX = '/^(?:[0-9]{1,3}\.){0,3}[0-9]{1,3}(\:\d{1,5})?$/';
    public function __invoke(Builder $query, $value, string $property)
    {
        if ($query->getQuery()->from !== 'servers') {
            throw new \BadMethodCallException('Cannot use the MultiFieldServerFilter against a non-server model.');
        }
        if (preg_match(self::IPV4_REGEX, $value) || preg_match('/^:\d{1,5}$/', $value)) {
            $query
                ->select('servers.*')
                ->join('allocations', 'allocations.server_id', '=', 'servers.id')
                ->where(function (Builder $builder) use ($value) {
                    $parts = explode(':', $value);
                    $builder->when(
                        !Str::startsWith($value, ':'),
                        function (Builder $builder) use ($parts) {
                            $builder->orWhere('allocations.ip', 'LIKE', "{$parts[0]}%");
                            if (!is_null($parts[1] ?? null)) {
                                $builder->where('allocations.port', 'LIKE', "{$parts[1]}%");
                            }
                        },
                        function (Builder $builder) use ($value) {
                            $builder->orWhere('allocations.port', 'LIKE', substr($value, 1) . '%');
                        }
                    );
                })
                ->groupBy('servers.id');
            return;
        }
        $query
            ->select('servers.*')
            ->leftJoin('users', 'servers.owner_id', '=', 'users.id')
            ->where(function (Builder $builder) use ($value) {
                $builder->where('servers.uuid', $value)
                    ->orWhere('servers.uuid', 'LIKE', "$value%")
                    ->orWhere('servers.uuidShort', $value)
                    ->orWhere('servers.external_id', $value)
                    ->orWhereRaw('LOWER(servers.name) LIKE ?', ["%$value%"])
                    ->orWhereRaw('LOWER(users.username) LIKE ?', ["%$value%"])
                    ->orWhereRaw('LOWER(users.email) LIKE ?', ["%$value%"]);
            })
            ->groupBy('servers.id');
    }
}
