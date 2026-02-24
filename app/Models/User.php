<?php

namespace App\Models;

use App\Notifications\ResetPasswordBrandNotification;
use Illuminate\Auth\Passwords\CanResetPassword as CanResetPasswordTrait;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements CanResetPasswordContract, FilamentUser
{
    use HasRoles, HasFactory, Notifiable, CanResetPasswordTrait;

    protected $fillable = [
        'name',
        'email',
        'password',
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
        'birth_date' => 'date',
        'teacher_hourly_rate_gross' => 'decimal:2',
        'teacher_subjects' => 'array',
        'password' => 'hashed',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /* public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasAnyRole(['superadmin', 'super_admin', 'Amministrazione', 'Segreteria']);
    } */



public function canAccessPanel(Panel $panel): bool
{
    return match ($panel->getId()) {
        'superadmin' => $this->hasAnyRole(['superadmin', 'super_admin']),
        'admin'      => $this->hasAnyRole(['superadmin', 'super_admin', 'Amministrazione', 'Segreteria']),
        'docente'    => $this->hasRole('Docente'),
        'studente'   => $this->hasRole('Studente'), // quando lo aggiungi
        default      => false,
    };
}



    public function isSuperAdmin(): bool
    {
        return $this->hasAnyRole(['superadmin', 'super_admin']);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordBrandNotification($token));
    }
}
