<?php

namespace App\Filament\Studente\Concerns;

class HasStudentScope
{
    public static function studentIds(): array
    {
        return auth()->check()
            ? auth()->user()->students()->pluck('id')->map(fn ($id) => (int) $id)->all()
            : [];
    }
}