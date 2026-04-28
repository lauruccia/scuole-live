<?php

namespace App\Filament\Studente\Pages;

use App\Models\Contract;
use Filament\Pages\Page;

class ContrattoPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Contratto';
    protected static ?string $title = 'Il mio contratto';
    protected static string $view = 'filament.studente.pages.contratto-page';
    protected static bool $shouldRegisterNavigation = false;

    public ?Contract $contract = null;

    public function mount(): void
    {
        $student = auth()->user()?->student;

        if (! $student) {
            $this->contract = null;
            return;
        }

        $this->contract = Contract::query()
            ->with(['course'])
            ->whereHas('students', function ($query) use ($student) {
                $query->where('students.id', $student->id);
            })
            ->latest('id')
            ->first();
    }
}