<?php

use App\Models\BillingProfile;
use App\Models\CoursePurchase;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;

/**
 * Test unit: configurazione spatie/activitylog sui model GDPR/finanziari.
 *
 * Verifica che ogni model esponga i LogOptions corretti senza toccare il DB
 * (no migrations, no factories): basta istanziare il model e leggere
 * getActivitylogOptions().
 *
 * Coperto:
 *  - logName corretto per categoria
 *  - logOnlyDirty + dontSubmitEmptyLogs attivi
 *  - User esclude tassativamente password/remember_token
 *  - lista attributi tracciati include i campi sensibili attesi
 */

it('Student logga come gdpr con campi anagrafici', function () {
    $opts = (new Student())->getActivitylogOptions();

    expect($opts->logName)->toBe('gdpr');
    expect($opts->logOnlyDirty)->toBeTrue();
    expect($opts->submitEmptyLogs)->toBeFalse();

    expect($opts->logAttributes)->toContain('email');
    expect($opts->logAttributes)->toContain('fiscal_code');
    expect($opts->logAttributes)->toContain('birth_date');
    expect($opts->logAttributes)->toContain('parent_email');
});

it('BillingProfile logga come gdpr con campi fatturazione', function () {
    $opts = (new BillingProfile())->getActivitylogOptions();

    expect($opts->logName)->toBe('gdpr');
    expect($opts->logOnlyDirty)->toBeTrue();
    expect($opts->logAttributes)->toContain('fiscal_code');
    expect($opts->logAttributes)->toContain('vat_number');
    expect($opts->logAttributes)->toContain('pec');
});

it('CoursePurchase logga come payments con campi finanziari', function () {
    $opts = (new CoursePurchase())->getActivitylogOptions();

    expect($opts->logName)->toBe('payments');
    expect($opts->logOnlyDirty)->toBeTrue();
    expect($opts->logAttributes)->toContain('payment_status');
    expect($opts->logAttributes)->toContain('amount');
    expect($opts->logAttributes)->toContain('paid_at');
});

it('User logga come users senza mai esporre password o remember_token', function () {
    $opts = (new User())->getActivitylogOptions();

    expect($opts->logName)->toBe('users');
    expect($opts->logOnlyDirty)->toBeTrue();

    // ⚠️ Critical: questi attributi NON devono mai apparire
    expect($opts->logAttributes)->not->toContain('password');
    expect($opts->logAttributes)->not->toContain('remember_token');
    expect($opts->logAttributes)->not->toContain('cv_path');
    expect($opts->logAttributes)->not->toContain('id_doc_path');

    // Ma devono apparire i campi anagrafici/business
    expect($opts->logAttributes)->toContain('email');
    expect($opts->logAttributes)->toContain('must_change_password');
});

it('Role logga come permissions con i gruppi permessi', function () {
    $opts = (new Role())->getActivitylogOptions();

    expect($opts->logName)->toBe('permissions');
    expect($opts->logOnlyDirty)->toBeTrue();
    expect($opts->logAttributes)->toContain('name');
    expect($opts->logAttributes)->toContain('permissions_studenti');
    expect($opts->logAttributes)->toContain('permissions_didattica');
});

it('Permission logga come permissions con name e guard_name', function () {
    $opts = (new Permission())->getActivitylogOptions();

    expect($opts->logName)->toBe('permissions');
    expect($opts->logOnlyDirty)->toBeTrue();
    expect($opts->logAttributes)->toEqualCanonicalizing(['name', 'guard_name']);
});
