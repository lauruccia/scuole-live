<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    use LogsActivity;

    // ─── Activity Log (cambio permessi) ──────────────────────────────────────
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('permissions')
            ->logOnly(['name', 'guard_name'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName): string => match ($eventName) {
                'created' => "Permesso #{$this->id} creato — {$this->name}",
                'updated' => "Permesso #{$this->id} aggiornato — {$this->name}",
                'deleted' => "Permesso #{$this->id} eliminato — {$this->name}",
                default   => "Permesso #{$this->id} — {$eventName}",
            });
    }
}
