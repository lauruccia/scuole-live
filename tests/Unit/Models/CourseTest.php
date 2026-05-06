<?php

use App\Models\Course;

/**
 * Test unit per il model Course.
 *
 * Coperto:
 *  - total_price accessor (corso + iscrizione)
 *  - hours_personal accessor (totale - full)
 *  - cast booleani is_active / is_public
 */

it('total_price somma course_price + enrollment_fee', function () {
    $course = new Course();
    $course->course_price = 200.50;
    $course->enrollment_fee = 50.00;

    expect($course->total_price)->toBe(250.50);
});

it('total_price gestisce enrollment_fee a zero', function () {
    $course = new Course();
    $course->course_price = 100.00;
    $course->enrollment_fee = 0;

    expect($course->total_price)->toBe(100.00);
});

it('hours_personal sottrae hours_full da hours_purchased', function () {
    $course = new Course();
    $course->hours_purchased = 30;
    $course->hours_full = 12;

    expect($course->hours_personal)->toBe(18.0);
});

it('hours_personal non va mai sotto zero', function () {
    $course = new Course();
    $course->hours_purchased = 10;
    $course->hours_full = 25; // intenzionalmente > totale

    expect($course->hours_personal)->toBe(0.0);
});

it('hours_personal con hours_full null', function () {
    $course = new Course();
    $course->hours_purchased = 20;
    $course->hours_full = null;

    expect($course->hours_personal)->toBe(20.0);
});

it('cast is_public e is_active sono boolean', function () {
    $course = new Course();
    $course->is_active = '1';
    $course->is_public = 0;

    // I cast eloquent funzionano solo dopo save/refresh in pratica;
    // qui verifichiamo almeno che il modello dichiari il cast.
    $casts = $course->getCasts();
    expect($casts['is_active'])->toBe('boolean');
    expect($casts['is_public'])->toBe('boolean');
});
