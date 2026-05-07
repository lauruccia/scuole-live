<?php

namespace App\Models;

use App\Notifications\ResetPasswordBrandNotification;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Auth\Passwords\CanResetPassword as CanResetPasswordTrait;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements CanResetPasswordContract, FilamentUser
{
    use HasRoles, HasFactory, LogsActivity, Notifiable, CanResetPasswordTrait;

    // ─── Activity Log (account utente — esclude password) ────────────────────
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('users')
            ->logOnly([
                'name', 'email',
                'first_name', 'last_name', 'phone',
                'birth_date', 'birth_place', 'birth_country',
                'vat_number', 'tax_code',
                'address', 'zip', 'city', 'province', 'country',
                'pec', 'iban',
                'must_change_password', 'password_changed_at',
                'teacher_contract_type', 'teacher_hourly_rate_gross',
                'teacher_billing_mode', 'teacher_subjects',
                // ⚠️ NON includere mai: password, remember_token, cv_path, id_doc_path
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName): string => match ($eventName) {
                'created' => "Utente #{$this->id} creato — {$this->email}",
                'updated' => "Utente #{$this->id} aggiornato — {$this->email}",
                'deleted' => "Utente #{$this->id} eliminato — {$this->email}",
                default   => "Utente #{$this->id} — {$eventName}",
            });
    }


    protected $fillable = [
        'name',
        'email',
        'password',
        'must_change_password',
        'password_changed_at',
        'first_name',
        'last_name',
        'phone',
        'birth_date',
        'birth_place',
        'birth_country',
        'vat_number',
        'tax_code',
        'address',
        'zip',
        'city',
        'province',
        'country',
        'pec',
        'iban',
        'cv_path',
        'id_doc_path',
        'teacher_contract_type',
        'teacher_hourly_rate_gross',
        'teacher_billing_mode',
        'teacher_subjects',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password_changed_at' => 'datetime',
        'must_change_password' => 'boolean',
        'birth_date' => 'date',
        'teacher_hourly_rate_gross' => 'decimal:2',
        'teacher_subjects' => 'array',
        'password' => 'hashed',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        $isSuperAdmin = $this->hasAnyRole(['Superadmin', 'superadmin', 'super_admin']);

        return match ($panel->getId()) {
            'superadmin' => $isSuperAdmin,
            'admin' => $isSuperAdmin || $this->hasAnyRole(['Amministrazione', 'Segreteria']),
            'docente' => $this->hasRole('Docente') || $isSuperAdmin,
            'studente' => $this->hasRole('Studente') || $isSuperAdmin,
            default => false,
        };
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasAnyRole(['Superadmin', 'superadmin', 'super_admin']);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordBrandNotification($token));
    }

    public function students(): HasMany
    {
        return $this->hasMany(\App\Models\Student::class, 'user_id');
    }
}