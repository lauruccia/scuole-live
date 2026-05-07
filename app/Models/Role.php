<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use LogsActivity;

    // Casts custom dei nostri "gruppi permessi"
    protected $casts = [
        'permissions_report'        => 'array',
        'permissions_studenti'      => 'array',
        'permissions_didattica'     => 'array',
        'permissions_risorse'       => 'array',
        'permissions_impostazioni'  => 'array',
        'permissions_widget'        => 'array',
    ];

    // ─── Activity Log (cambio ruoli/privilegi) ───────────────────────────────
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('permissions')
            ->logOnly([
                'name', 'guard_name',
                'permissions_report',
                'permissions_studenti',
                'permissions_didattica',
                'permissions_risorse',
                'permissions_impostazioni',
                'permissions_widget',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName): string => match ($eventName) {
                'created' => "Ruolo #{$this->id} creato — {$this->name}",
                'updated' => "Ruolo #{$this->id} aggiornato — {$this->name}",
                'deleted' => "Ruolo #{$this->id} eliminato — {$this->name}",
                default   => "Ruolo #{$this->id} — {$eventName}",
            });
    }
}
