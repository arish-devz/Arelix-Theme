<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;


class ActivityLogSubject extends Pivot
{
    public $incrementing = true;
    public $timestamps = false;

    protected $table = 'activity_log_subjects';

    protected $guarded = ['id'];

    public function activityLog()
    {
        return $this->belongsTo(ActivityLog::class);
    }

    public function subject()
    {
        $morph = $this->morphTo();
        if (method_exists($morph, 'withTrashed')) {
            return $morph->withTrashed();
        }

        return $morph;
    }
}
