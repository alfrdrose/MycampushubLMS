<?php

namespace App\Models\Concerns;

use App\Support\AdminActivityLog;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

trait LogsAdminActivity
{
    use LogsActivity {
        LogsActivity::shouldLogEvent as protected shouldLogEventUsingSpatie;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return AdminActivityLog::options();
    }

    protected function shouldLogEvent(string $eventName): bool
    {
        if (! AdminActivityLog::shouldLog()) {
            return false;
        }

        return $this->shouldLogEventUsingSpatie($eventName);
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        AdminActivityLog::tap($activity);
    }
}
