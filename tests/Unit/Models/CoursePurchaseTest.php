<?php

use App\Models\CoursePurchase;
use Illuminate\Support\Carbon;

/**
 * Test unit per il model CoursePurchase.
 *
 * Coperto:
 *  - isPaid() / isPending() rispettano payment_status
 *  - generateBankRef formato BNF-YYYYMMDD-NNNNN con padding zeri
 *  - payment_method_label fa lookup sul match
 */

it('isPaid restituisce true solo se payment_status = paid', function () {
    $p = new CoursePurchase();
    $p->payment_status = 'paid';
    expect($p->isPaid())->toBeTrue();

    $p->payment_status = 'pending';
    expect($p->isPaid())->toBeFalse();

    $p->payment_status = 'cancelled';
    expect($p->isPaid())->toBeFalse();
});

it('isPending e\' true solo per status pending', function () {
    $p = new CoursePurchase();
    $p->payment_status = 'pending';
    expect($p->isPending())->toBeTrue();

    $p->payment_status = 'paid';
    expect($p->isPending())->toBeFalse();
});

it('generateBankRef ha il formato BNF-YYYYMMDD-NNNNN con padding zeri', function () {
    Carbon::setTestNow('2026-05-06 10:00:00');

    expect(CoursePurchase::generateBankRef(1))
        ->toBe('BNF-20260506-00001');

    expect(CoursePurchase::generateBankRef(42))
        ->toBe('BNF-20260506-00042');

    expect(CoursePurchase::generateBankRef(99999))
        ->toBe('BNF-20260506-99999');

    Carbon::setTestNow(); // reset
});

it('generateBankRef gestisce id molto grandi senza troncamento', function () {
    Carbon::setTestNow('2027-01-15 12:00:00');

    expect(CoursePurchase::generateBankRef(1234567))
        ->toBe('BNF-20270115-1234567');

    Carbon::setTestNow();
});

it('payment_method_label restituisce label leggibile per i metodi noti', function () {
    $p = new CoursePurchase();

    $p->payment_method = 'stripe';
    expect($p->payment_method_label)->toBe('Carta di credito');

    $p->payment_method = 'paypal';
    expect($p->payment_method_label)->toBe('PayPal');

    $p->payment_method = 'bonifico';
    expect($p->payment_method_label)->toBe('Bonifico bancario');
});

it('payment_method_label fallback al metodo grezzo se sconosciuto', function () {
    $p = new CoursePurchase();
    $p->payment_method = 'unknown_method';
    expect($p->payment_method_label)->toBe('unknown_method');
});
