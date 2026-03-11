<?php

namespace App\Models;

use App\Notifications\ResetPasswordBrandNotification;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Auth\Passwords\CanResetPassword as CanResetPasswordTrait;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

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

        // ✅ se assegni una stringa "Password123!" lui la hasha da solo
        'password' => 'hashed',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * ✅ Accesso ai panel Filament (multi-panel).
     * Attenzione: i nomi ruolo devono combaciare ESATTAMENTE (maiuscole incluse).
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Superadmin: copriamo più varianti (nel tuo DB potresti averle diverse)
        $isSuperAdmin = $this->hasAnyRole(['Superadmin', 'superadmin', 'super_admin']);

        return match ($panel->getId()) {
            'superadmin' => $isSuperAdmin,

            'admin'      => $isSuperAdmin
                || $this->hasAnyRole(['Amministrazione', 'Segreteria']),

            // ✅ Panel docente: solo Docente (ma per debugging puoi lasciare anche superadmin)
            'docente'    => $this->hasRole('Docente') || $isSuperAdmin,

            // quando lo aggiungi davvero
            'studente'   => $this->hasRole('Studente'),

            default      => false,
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
}
