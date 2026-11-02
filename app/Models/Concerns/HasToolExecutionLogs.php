<?php

namespace App\Models\Concerns;

use App\Models\ToolExecutionLog;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasToolExecutionLogs
{
    public function toolExecutionLogs(): MorphMany
    {
        return $this->morphMany(ToolExecutionLog::class, 'loggable');
    }

    protected static function bootHasToolExecutionLogs(): void
    {
        static::deleting(function ($model) {
            $model->toolExecutionLogs()->delete();
        });
    }
}
