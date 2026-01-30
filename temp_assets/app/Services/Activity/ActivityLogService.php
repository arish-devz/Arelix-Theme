<?php

namespace Pterodactyl\Services\Activity;

use Illuminate\Support\Arr;
use Webmozart\Assert\Assert;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;
use Pterodactyl\Models\ActivityLogSubject;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Contracts\Auth\Factory as AuthFactory;

class ActivityLogService
{
    protected ?ActivityLog $activity = null;

    protected array $subjects = [];

    public function __construct(
        protected AuthFactory $manager,
        protected ActivityLogBatchService $batch,
        protected ActivityLogTargetableService $targetable,
        protected ConnectionInterface $connection
    ) {
    }

    
    public function anonymous(): self
    {
        $this->getActivity()->actor_id = null;
        $this->getActivity()->actor_type = null;
        $this->getActivity()->setRelation('actor', null);

        return $this;
    }

    
    public function event(string $action): self
    {
        $this->getActivity()->event = $action;

        return $this;
    }

    
    public function description(?string $description): self
    {
        $this->getActivity()->description = $description;

        return $this;
    }

    
    public function subject(...$subjects): self
    {
        foreach (Arr::wrap($subjects) as $subject) {
            if (is_null($subject)) {
                continue;
            }

            foreach ($this->subjects as $entry) {
                
                
                if ($entry->is($subject)) {
                    continue 2;
                }
            }

            $this->subjects[] = $subject;
        }

        return $this;
    }

    
    public function actor(Model $actor): self
    {
        $this->getActivity()->actor()->associate($actor);

        return $this;
    }

    
    public function property($key, $value = null): self
    {
        $properties = $this->getActivity()->properties;
        $this->activity->properties = is_array($key)
            ? $properties->merge($key)
            : $properties->put($key, $value);

        return $this;
    }

    
    public function withRequestMetadata(): self
    {
        return $this->property([
            'ip' => Request::getClientIp(),
            'useragent' => Request::userAgent(),
        ]);
    }

    
    public function log(string $description = null): ActivityLog
    {
        $activity = $this->getActivity();

        if (!is_null($description)) {
            $activity->description = $description;
        }

        try {
            return $this->save();
        } catch (\Throwable|\Exception $exception) {
            if (config('app.env') !== 'production') {
                
                throw $exception;
            }

            Log::error($exception);
        }

        return $activity;
    }

    
    public function clone(): self
    {
        return clone $this;
    }

    
    public function transaction(\Closure $callback)
    {
        return $this->connection->transaction(function () use ($callback) {
            $response = $callback($this);

            $this->save();

            return $response;
        });
    }

    
    public function reset(): void
    {
        $this->activity = null;
        $this->subjects = [];
    }

    
    protected function getActivity(): ActivityLog
    {
        if ($this->activity) {
            return $this->activity;
        }

        $this->activity = new ActivityLog([
            'ip' => Request::ip(),
            'batch_uuid' => $this->batch->uuid(),
            'properties' => Collection::make([]),
            'api_key_id' => $this->targetable->apiKeyId(),
        ]);

        if ($subject = $this->targetable->subject()) {
            $this->subject($subject);
        }

        if ($actor = $this->targetable->actor()) {
            $this->actor($actor);
        } elseif ($user = $this->manager->guard()->user()) {
            if ($user instanceof Model) {
                $this->actor($user);
            }
        }

        return $this->activity;
    }

    
    protected function save(): ActivityLog
    {
        Assert::notNull($this->activity);

        $response = $this->connection->transaction(function () {
            $this->activity->save();

            $subjects = Collection::make($this->subjects)
                ->map(fn (Model $subject) => [
                    'activity_log_id' => $this->activity->id,
                    'subject_id' => $subject->getKey(),
                    'subject_type' => $subject->getMorphClass(),
                ])
                ->values()
                ->toArray();

            ActivityLogSubject::insert($subjects);

            return $this->activity;
        });

        $this->activity = null;
        $this->subjects = [];

        return $response;
    }
}
